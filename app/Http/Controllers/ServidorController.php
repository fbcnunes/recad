<?php

namespace App\Http\Controllers;

use App\Models\CertidaoServidor;
use App\Models\ContaBancaria;
use App\Models\ContatoEmergencia;
use App\Models\Dependente;
use App\Models\DocumentoPessoal;
use App\Models\Servidor;
use App\Services\ServidorConfirmacaoService;
use App\Models\Vinculo;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServidorController extends Controller
{
    public function showSelf(Request $request)
    {
        $matricula = $request->session()->get('ldap_pager');
        $svc = new ServidorConfirmacaoService();
        $editTab = trim((string) $request->query('edit_tab', ''));
        if ($editTab !== '' && !ServidorConfirmacaoService::isValidTab($editTab)) {
            $editTab = '';
        }

        if (!$matricula) {
            return view('servidores.profile', [
                'servidor' => null,
                'editTab' => null,
                'matricula' => null,
                'notFound' => 'Matrícula não encontrada no AD. Procure a DTI/CORI.',
                'createMode' => false,
                'tabs' => ServidorConfirmacaoService::labels(),
                'confirmacoes' => [],
                'allConfirmed' => false,
            ]);
        }

        $servidor = Servidor::query()
            ->with([
                'documentoPessoal',
                'certidaoServidor',
                'vinculo',
                'contaBancaria',
                'contatosEmergencia',
                'dependentes',
                'confirmacoes',
            ])
            ->where('matricula', $matricula)
            ->first();

        if (!$servidor) {
            return view('servidores.profile', [
                'servidor' => null,
                // In create mode, force first save through "Dados pessoais".
                'editTab' => 'pessoais',
                'matricula' => $matricula,
                'notFound' => null,
                'createMode' => true,
                'tabs' => ServidorConfirmacaoService::labels(),
                'confirmacoes' => [],
                'allConfirmed' => false,
            ]);
        }

        // Auto-invalidate confirmations if the underlying data changed.
        $svc->syncStaleConfirmacoes($servidor);
        $servidor->loadMissing('confirmacoes');
        $confirmacoes = $servidor->confirmacoes->keyBy('aba')->all();
        $allConfirmed = $svc->allTabsConfirmed($servidor);

        return view('servidores.profile', [
            'servidor' => $servidor,
            'editTab' => $editTab !== '' ? $editTab : null,
            'matricula' => $matricula,
            'notFound' => null,
            'createMode' => false,
            'tabs' => ServidorConfirmacaoService::labels(),
            'confirmacoes' => $confirmacoes,
            'allConfirmed' => $allConfirmed,
        ]);
    }

    public function updateSelf(Request $request)
    {
        $matricula = $request->session()->get('ldap_pager');
        if (!$matricula) {
            return redirect()->route('servidores.self')
                ->withErrors(['matricula' => 'Matrícula não encontrada. Procure a DTI/CORI.']);
        }

        $this->uppercaseTextInputs($request);

        $tab = (string) $request->input('_active_tab', 'pessoais');
        if (!ServidorConfirmacaoService::isValidTab($tab)) {
            $tab = 'pessoais';
        }

        $servidor = Servidor::where('matricula', $matricula)->first();
        $creating = false;
        if (!$servidor) {
            $creating = true;
            $servidor = new Servidor(['matricula' => $matricula]);
        }

        if ($creating && $tab !== 'pessoais') {
            return redirect()
                ->to(route('servidores.self') . '#pessoais')
                ->withErrors(['servidor' => 'Preencha e salve "Dados pessoais" antes de editar outras abas.']);
        }

        $documento = $servidor->documentoPessoal ?? null;
        $data = $this->validateTabData($tab, $request, $servidor, $documento, $creating);

        $svc = new ServidorConfirmacaoService();
        $servidor->loadMissing([
            'documentoPessoal',
            'certidaoServidor',
            'vinculo',
            'contaBancaria',
            'contatosEmergencia',
            'dependentes',
            'confirmacoes',
        ]);
        $existingConf = $servidor->confirmacoes->firstWhere('aba', $tab);
        $oldHash = $existingConf?->hash_snapshot;

        DB::transaction(function () use ($tab, $servidor, $creating, $documento, $data) {
            if ($tab === 'pessoais') {
                $servidorData = $this->only($data, [
                    'nome', 'pai', 'mae', 'data_nascimento', 'estado_civil', 'conjuge_nome',
                    'naturalidade', 'naturalidade_uf', 'nacionalidade', 'escolaridade', 'curso',
                    'pos_graduacao', 'pos_curso', 'pos_inicio', 'pos_fim', 'pos_carga_horaria', 'sexo',
                    'tipo_sanguineo', 'fator_rh', 'raca_cor',
                ]);

                if ($creating) {
                    $servidor->fill($servidorData);
                    $servidor->save();
                } else {
                    $servidor->update($servidorData);
                }

                return;
            }

            if ($tab === 'endereco') {
                $servidorData = $this->only($data, [
                    'endereco', 'numero', 'bairro', 'complemento', 'cep', 'cidade', 'cidade_uf',
                    'fone_fixo', 'celular', 'email', 'plano_saude',
                ]);
                $servidor->update($servidorData);
                return;
            }

            if ($tab === 'documentos') {
                $documentoData = $this->only($data, [
                    'rg_num', 'rg_uf', 'rg_expedicao', 'cpf', 'id_prof_num', 'id_prof_tipo', 'id_prof_uf',
                    'cnh_num', 'cnh_categoria', 'cnh_validade', 'cnh_uf', 'ctps_num', 'ctps_serie',
                    'ctps_expedicao', 'titulo_eleitor_num', 'titulo_zona', 'titulo_secao', 'reservista_num',
                    'reservista_categoria', 'reservista_uf', 'pis_pasep',
                ]);

                if ($documento) {
                    // Allow clearing values: always update when the record exists.
                    $documento->update($documentoData);
                } elseif ($this->hasAny($documentoData)) {
                    DocumentoPessoal::create(array_merge($documentoData, [
                        'servidor_id' => $servidor->id,
                    ]));
                }
                return;
            }

            if ($tab === 'certidao') {
                $certidaoData = [
                    'tipo' => $data['certidao_tipo'] ?? null,
                    'registro_num' => $data['certidao_registro_num'] ?? null,
                    'livro' => $data['certidao_livro'] ?? null,
                    'folha' => $data['certidao_folha'] ?? null,
                    'matricula' => $data['certidao_matricula'] ?? null,
                ];
                $exists = $servidor->certidaoServidor()->exists();
                if ($exists || $this->hasAny($certidaoData)) {
                    $servidor->certidaoServidor()
                        ->updateOrCreate(['servidor_id' => $servidor->id], $certidaoData);
                }
                return;
            }

            if ($tab === 'ingresso') {
                $vinculoData = $this->only($data, [
                    'forma_ingresso', 'data_ingresso', 'nomeacao_cessao_data', 'portaria_num', 'doe_num',
                    'doe_publicacao_data', 'cargo_funcao', 'orgao_origem',
                ]);
                $exists = $servidor->vinculo()->exists();
                if ($exists || $this->hasAny($vinculoData)) {
                    $servidor->vinculo()
                        ->updateOrCreate(['servidor_id' => $servidor->id], $vinculoData);
                }
                return;
            }

            if ($tab === 'banco') {
                $contaData = $this->only($data, [
                    'banco_num', 'agencia_num', 'conta_corrente_num',
                ]);
                $exists = $servidor->contaBancaria()->exists();
                if ($exists || $this->hasAny($contaData)) {
                    $servidor->contaBancaria()
                        ->updateOrCreate(['servidor_id' => $servidor->id], $contaData);
                }
                return;
            }

            if ($tab === 'emergencia') {
                $items = $data['contatos_emergencia'] ?? [];
                foreach ($items as $item) {
                    if (!is_array($item)) continue;
                    $id = $item['id'] ?? null;
                    $del = (string) ($item['_delete'] ?? '') === '1';
                    $payload = $this->only($item, ['nome', 'celular', 'parentesco']);

                    if ($id) {
                        $row = ContatoEmergencia::where('servidor_id', $servidor->id)->where('id', $id)->first();
                        if (!$row) continue;
                        if ($del) {
                            $row->delete();
                            continue;
                        }
                        // Allow clearing values: always update when the record exists.
                        $row->update($payload);
                        continue;
                    }

                    if ($del) continue;
                    if ($this->hasAny($payload)) {
                        ContatoEmergencia::create(array_merge($payload, ['servidor_id' => $servidor->id]));
                    }
                }
                return;
            }

            if ($tab === 'dependentes') {
                $items = $data['dependentes'] ?? [];
                foreach ($items as $item) {
                    if (!is_array($item)) continue;
                    $id = $item['id'] ?? null;
                    $del = (string) ($item['_delete'] ?? '') === '1';
                    $payload = $this->only($item, [
                        'nome', 'parentesco', 'nascimento', 'rg_num', 'rg_expedicao', 'cpf',
                        'certidao_tipo', 'sexo', 'tipo_dependente',
                    ]);

                    if ($id) {
                        $row = Dependente::where('servidor_id', $servidor->id)->where('id', $id)->first();
                        if (!$row) continue;
                        if ($del) {
                            $row->delete();
                            continue;
                        }
                        // Allow clearing values: always update when the record exists.
                        $row->update($payload);
                        continue;
                    }

                    if ($del) continue;
                    if ($this->hasAny($payload)) {
                        Dependente::create(array_merge($payload, ['servidor_id' => $servidor->id]));
                    }
                }
                return;
            }
        });

        // Reload saved state and invalidate only the current tab confirmation if the data changed.
        $servidor = Servidor::query()
            ->with([
                'documentoPessoal',
                'certidaoServidor',
                'vinculo',
                'contaBancaria',
                'contatosEmergencia',
                'dependentes',
                'confirmacoes',
            ])
            ->findOrFail($servidor->id);

        if ($oldHash) {
            $newHash = $svc->hashFor($servidor, $tab);
            if (!hash_equals((string) $oldHash, (string) $newHash)) {
                DB::transaction(function () use ($servidor, $tab) {
                    $servidor->confirmacoes()->where('aba', $tab)->delete();
                    if ($servidor->recadastramento_concluido_em) {
                        $servidor->recadastramento_concluido_em = null;
                        $servidor->recadastramento_concluido_por_user_id = null;
                        $servidor->save();
                    }
                });
            }
        }

        return redirect()
            ->to(route('servidores.self') . '#' . $tab)
            ->with('status', 'Dados atualizados com sucesso.');
    }

    public function confirmTab(Request $request, string $aba)
    {
        $matricula = $request->session()->get('ldap_pager');
        if (!$matricula) {
            return redirect()->route('servidores.self')
                ->withErrors(['matricula' => 'Matrícula não encontrada no AD. Procure a DTI/CORI.']);
        }
        if (!ServidorConfirmacaoService::isValidTab($aba)) {
            return redirect()->route('servidores.self')->withErrors(['aba' => 'Aba inválida.']);
        }

        $tab = (string) $request->input('_active_tab', $aba);
        if (!ServidorConfirmacaoService::isValidTab($tab)) $tab = $aba;

        $servidor = Servidor::query()
            ->with(['documentoPessoal','certidaoServidor','vinculo','contaBancaria','contatosEmergencia','dependentes'])
            ->where('matricula', $matricula)
            ->first();

        if (!$servidor) {
            return redirect()
                ->to(route('servidores.self') . '#' . $tab)
                ->withErrors(['servidor' => 'Salve seu cadastro antes de confirmar as abas.']);
        }

        $svc = new ServidorConfirmacaoService();
        $svc->confirmTab($servidor, $aba, Auth::id());

        return redirect()
            ->to(route('servidores.self') . '#' . $tab)
            ->with('status', 'Aba confirmada.');
    }

    public function unconfirmTab(Request $request, string $aba)
    {
        $matricula = $request->session()->get('ldap_pager');
        if (!$matricula) {
            return redirect()->route('servidores.self')
                ->withErrors(['matricula' => 'Matrícula não encontrada no AD. Procure a DTI/CORI.']);
        }
        if (!ServidorConfirmacaoService::isValidTab($aba)) {
            return redirect()->route('servidores.self')->withErrors(['aba' => 'Aba inválida.']);
        }

        $tab = (string) $request->input('_active_tab', $aba);
        if (!ServidorConfirmacaoService::isValidTab($tab)) $tab = $aba;

        $servidor = Servidor::where('matricula', $matricula)->first();
        if (!$servidor) {
            return redirect()->to(route('servidores.self') . '#' . $tab);
        }

        $svc = new ServidorConfirmacaoService();
        $svc->unconfirmTab($servidor, $aba);

        return redirect()
            ->to(route('servidores.self') . '#' . $tab)
            ->with('status', 'Confirmação removida.');
    }

    public function concluirRecadastramento(Request $request)
    {
        $matricula = $request->session()->get('ldap_pager');
        if (!$matricula) {
            return redirect()->route('servidores.self')
                ->withErrors(['matricula' => 'Matrícula não encontrada no AD. Procure a DTI/CORI.']);
        }

        $tab = (string) $request->input('_active_tab', 'pessoais');
        if (!ServidorConfirmacaoService::isValidTab($tab)) $tab = 'pessoais';

        $servidor = Servidor::query()
            ->with([
                'documentoPessoal',
                'certidaoServidor',
                'vinculo',
                'contaBancaria',
                'contatosEmergencia',
                'dependentes',
                'confirmacoes',
            ])
            ->where('matricula', $matricula)
            ->first();

        if (!$servidor) {
            return redirect()->to(route('servidores.self') . '#' . $tab)
                ->withErrors(['servidor' => 'Cadastro não encontrado. Preencha e salve antes de concluir.']);
        }

        $svc = new ServidorConfirmacaoService();
        $svc->syncStaleConfirmacoes($servidor);

        if (!$svc->allTabsConfirmed($servidor)) {
            return redirect()->to(route('servidores.self') . '#' . $tab)
                ->withErrors(['conclusao' => 'Para concluir, confirme todas as abas.']);
        }

        $servidor->recadastramento_concluido_em = now();
        $servidor->recadastramento_concluido_por_user_id = Auth::id();
        $servidor->save();

        return redirect()
            ->to(route('servidores.self') . '#' . $tab)
            ->with('status', 'Recadastramento concluído.');
    }

    public function pdf(Request $request)
    {
        $matricula = $request->session()->get('ldap_pager');
        if (!$matricula) {
            return redirect()->route('servidores.self')
                ->withErrors(['matricula' => 'Matrícula não encontrada no AD. Procure a DTI/CORI.']);
        }

        $servidor = Servidor::query()
            ->with([
                'documentoPessoal',
                'certidaoServidor',
                'vinculo',
                'contaBancaria',
                'contatosEmergencia',
                'dependentes',
                'confirmacoes',
            ])
            ->where('matricula', $matricula)
            ->firstOrFail();

        $printAt = now();
        $html = view('servidores.pdf', [
            'servidor' => $servidor,
            'printAt' => $printAt,
            'tabs' => ServidorConfirmacaoService::labels(),
        ])->render();

        $dompdf = new Dompdf([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'recad_' . $servidor->matricula . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function only(array $data, array $keys): array
    {
        return array_intersect_key($data, array_flip($keys));
    }

    private function hasAny(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function validateData(Request $request, Servidor $servidor, ?DocumentoPessoal $documento): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'pai' => ['nullable', 'string', 'max:255'],
            'mae' => ['nullable', 'string', 'max:255'],
            'data_nascimento' => ['nullable', 'date'],
            'estado_civil' => ['nullable', 'string', 'max:255', Rule::in(config('recad.estado_civil'))],
            'conjuge_nome' => ['nullable', 'string', 'max:255'],
            'naturalidade' => ['nullable', 'string', 'max:255'],
            'naturalidade_uf' => ['nullable', 'string', 'size:2'],
            'nacionalidade' => ['nullable', 'string', 'max:255'],
            'escolaridade' => ['nullable', 'string', 'max:255', Rule::in(config('recad.escolaridade'))],
            'curso' => ['nullable', 'string', 'max:255'],
            'pos_graduacao' => ['nullable', 'string', 'max:255'],
            'pos_curso' => ['nullable', 'string', 'max:255'],
            'pos_inicio' => ['nullable', 'date'],
            'pos_fim' => ['nullable', 'date'],
            'pos_carga_horaria' => ['nullable', 'integer', 'min:0'],
            'sexo' => ['nullable', 'string', 'max:50', Rule::in(config('recad.sexo'))],
            'tipo_sanguineo' => ['nullable', 'string', 'max:3', Rule::in(config('recad.tipo_sanguineo'))],
            'fator_rh' => ['nullable', 'string', 'max:3', Rule::in(config('recad.fator_rh'))],
            'raca_cor' => ['nullable', 'string', 'max:50', Rule::in(config('recad.raca_cor'))],
            'endereco' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:50'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:10'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'cidade_uf' => ['nullable', 'string', 'size:2'],
            'fone_fixo' => ['nullable', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('servidores', 'email')->ignore($servidor->id)],
            'plano_saude' => ['nullable', 'string', 'max:255'],

            'rg_num' => ['nullable', 'string', 'max:50'],
            'rg_uf' => ['nullable', 'string', 'size:2'],
            'rg_expedicao' => ['nullable', 'date'],
            'cpf' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('documentos_pessoais', 'cpf')->ignore($documento?->id),
            ],
            'id_prof_num' => ['nullable', 'string', 'max:50'],
            'id_prof_tipo' => ['nullable', 'string', 'max:50'],
            'id_prof_uf' => ['nullable', 'string', 'size:2'],
            'cnh_num' => ['nullable', 'string', 'max:50'],
            'cnh_categoria' => ['nullable', 'string', 'max:10'],
            'cnh_validade' => ['nullable', 'date'],
            'cnh_uf' => ['nullable', 'string', 'size:2'],
            'ctps_num' => ['nullable', 'string', 'max:50'],
            'ctps_serie' => ['nullable', 'string', 'max:50'],
            'ctps_expedicao' => ['nullable', 'date'],
            'titulo_eleitor_num' => ['nullable', 'string', 'max:50'],
            'titulo_zona' => ['nullable', 'string', 'max:50'],
            'titulo_secao' => ['nullable', 'string', 'max:50'],
            'reservista_num' => ['nullable', 'string', 'max:50'],
            'reservista_categoria' => ['nullable', 'string', 'max:50'],
            'reservista_uf' => ['nullable', 'string', 'size:2'],
            'pis_pasep' => ['nullable', 'string', 'max:50'],

            'certidao_tipo' => ['nullable', 'string', 'max:50', Rule::in(config('recad.certidao_tipo'))],
            'certidao_registro_num' => ['nullable', 'string', 'max:50'],
            'certidao_livro' => ['nullable', 'string', 'max:50'],
            'certidao_folha' => ['nullable', 'string', 'max:50'],
            'certidao_matricula' => ['nullable', 'string', 'max:50'],

            'forma_ingresso' => ['nullable', 'string', 'max:50', Rule::in(config('recad.forma_ingresso'))],
            'data_ingresso' => ['nullable', 'date'],
            'nomeacao_cessao_data' => ['nullable', 'date'],
            'portaria_num' => ['nullable', 'string', 'max:50'],
            'doe_num' => ['nullable', 'string', 'max:50'],
            'doe_publicacao_data' => ['nullable', 'date'],
            'cargo_funcao' => ['nullable', 'string', 'max:255'],
            'orgao_origem' => ['nullable', 'string', 'max:255'],

            'banco_num' => ['nullable', 'string', 'max:10'],
            'agencia_num' => ['nullable', 'string', 'max:20'],
            'conta_corrente_num' => ['nullable', 'string', 'max:20'],

            'contatos_emergencia' => ['nullable', 'array'],
            'contatos_emergencia.*.nome' => ['nullable', 'string', 'max:255'],
            'contatos_emergencia.*.celular' => ['nullable', 'string', 'max:50'],
            'contatos_emergencia.*.parentesco' => ['nullable', 'string', 'max:100'],

            'dependentes' => ['nullable', 'array'],
            'dependentes.*.nome' => ['nullable', 'string', 'max:255'],
            'dependentes.*.parentesco' => ['nullable', 'string', 'max:100'],
            'dependentes.*.nascimento' => ['nullable', 'date'],
            'dependentes.*.rg_num' => ['nullable', 'string', 'max:50'],
            'dependentes.*.rg_expedicao' => ['nullable', 'date'],
            'dependentes.*.cpf' => ['nullable', 'string', 'max:20'],
            'dependentes.*.certidao_tipo' => ['nullable', 'string', 'max:50', Rule::in(config('recad.certidao_tipo'))],
            'dependentes.*.sexo' => ['nullable', 'string', 'max:50', Rule::in(config('recad.sexo'))],
            'dependentes.*.tipo_dependente' => ['nullable', 'string', 'max:50', Rule::in(config('recad.dependente_tipo'))],
        ]);
    }

    private function validateTabData(
        string $tab,
        Request $request,
        Servidor $servidor,
        ?DocumentoPessoal $documento,
        bool $creating
    ): array {
        $rules = [];

        if ($tab === 'pessoais') {
            $rules = [
                'nome' => [$creating ? 'required' : 'required', 'string', 'max:255'],
                'pai' => ['nullable', 'string', 'max:255'],
                'mae' => ['nullable', 'string', 'max:255'],
                'data_nascimento' => ['nullable', 'date'],
                'estado_civil' => ['nullable', 'string', 'max:255', Rule::in(config('recad.estado_civil'))],
                'conjuge_nome' => ['nullable', 'string', 'max:255'],
                'naturalidade' => ['nullable', 'string', 'max:255'],
                'naturalidade_uf' => ['nullable', 'string', 'size:2'],
                'nacionalidade' => ['nullable', 'string', 'max:255'],
                'escolaridade' => ['nullable', 'string', 'max:255', Rule::in(config('recad.escolaridade'))],
                'curso' => ['nullable', 'string', 'max:255'],
                'pos_graduacao' => ['nullable', 'string', 'max:255'],
                'pos_curso' => ['nullable', 'string', 'max:255'],
                'pos_inicio' => ['nullable', 'date'],
                'pos_fim' => ['nullable', 'date'],
                'pos_carga_horaria' => ['nullable', 'integer', 'min:0'],
                'sexo' => ['nullable', 'string', 'max:50', Rule::in(config('recad.sexo'))],
                'tipo_sanguineo' => ['nullable', 'string', 'max:3', Rule::in(config('recad.tipo_sanguineo'))],
                'fator_rh' => ['nullable', 'string', 'max:3', Rule::in(config('recad.fator_rh'))],
                'raca_cor' => ['nullable', 'string', 'max:50', Rule::in(config('recad.raca_cor'))],
            ];
        } elseif ($tab === 'endereco') {
            $rules = [
                'endereco' => ['nullable', 'string', 'max:255'],
                'numero' => ['nullable', 'string', 'max:50'],
                'bairro' => ['nullable', 'string', 'max:255'],
                'complemento' => ['nullable', 'string', 'max:255'],
                'cep' => ['nullable', 'string', 'max:10'],
                'cidade' => ['nullable', 'string', 'max:255'],
                'cidade_uf' => ['nullable', 'string', 'size:2'],
                'fone_fixo' => ['nullable', 'string', 'max:50'],
                'celular' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('servidores', 'email')->ignore($servidor->id)],
                'plano_saude' => ['nullable', 'string', 'max:255'],
            ];
        } elseif ($tab === 'documentos') {
            $rules = [
                'rg_num' => ['nullable', 'string', 'max:50'],
                'rg_uf' => ['nullable', 'string', 'size:2'],
                'rg_expedicao' => ['nullable', 'date'],
                'cpf' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('documentos_pessoais', 'cpf')->ignore($documento?->id),
                ],
                'id_prof_num' => ['nullable', 'string', 'max:50'],
                'id_prof_tipo' => ['nullable', 'string', 'max:50'],
                'id_prof_uf' => ['nullable', 'string', 'size:2'],
                'cnh_num' => ['nullable', 'string', 'max:50'],
                'cnh_categoria' => ['nullable', 'string', 'max:10'],
                'cnh_validade' => ['nullable', 'date'],
                'cnh_uf' => ['nullable', 'string', 'size:2'],
                'ctps_num' => ['nullable', 'string', 'max:50'],
                'ctps_serie' => ['nullable', 'string', 'max:50'],
                'ctps_expedicao' => ['nullable', 'date'],
                'titulo_eleitor_num' => ['nullable', 'string', 'max:50'],
                'titulo_zona' => ['nullable', 'string', 'max:50'],
                'titulo_secao' => ['nullable', 'string', 'max:50'],
                'reservista_num' => ['nullable', 'string', 'max:50'],
                'reservista_categoria' => ['nullable', 'string', 'max:50'],
                'reservista_uf' => ['nullable', 'string', 'size:2'],
                'pis_pasep' => ['nullable', 'string', 'max:50'],
            ];
        } elseif ($tab === 'certidao') {
            $rules = [
                'certidao_tipo' => ['nullable', 'string', 'max:50', Rule::in(config('recad.certidao_tipo'))],
                'certidao_registro_num' => ['nullable', 'string', 'max:50'],
                'certidao_livro' => ['nullable', 'string', 'max:50'],
                'certidao_folha' => ['nullable', 'string', 'max:50'],
                'certidao_matricula' => ['nullable', 'string', 'max:50'],
            ];
        } elseif ($tab === 'ingresso') {
            $rules = [
                'forma_ingresso' => ['nullable', 'string', 'max:50', Rule::in(config('recad.forma_ingresso'))],
                'data_ingresso' => ['nullable', 'date'],
                'nomeacao_cessao_data' => ['nullable', 'date'],
                'portaria_num' => ['nullable', 'string', 'max:50'],
                'doe_num' => ['nullable', 'string', 'max:50'],
                'doe_publicacao_data' => ['nullable', 'date'],
                'cargo_funcao' => ['nullable', 'string', 'max:255'],
                'orgao_origem' => ['nullable', 'string', 'max:255'],
            ];
        } elseif ($tab === 'banco') {
            $rules = [
                'banco_num' => ['nullable', 'string', 'max:10'],
                'agencia_num' => ['nullable', 'string', 'max:20'],
                'conta_corrente_num' => ['nullable', 'string', 'max:20'],
            ];
        } elseif ($tab === 'emergencia') {
            $rules = [
                'contatos_emergencia' => ['nullable', 'array'],
                'contatos_emergencia.*.id' => ['nullable', 'integer'],
                'contatos_emergencia.*._delete' => ['nullable', 'in:0,1'],
                'contatos_emergencia.*.nome' => ['nullable', 'string', 'max:255'],
                'contatos_emergencia.*.celular' => ['nullable', 'string', 'max:50'],
                'contatos_emergencia.*.parentesco' => ['nullable', 'string', 'max:100'],
            ];
        } elseif ($tab === 'dependentes') {
            $rules = [
                'dependentes' => ['nullable', 'array'],
                'dependentes.*.id' => ['nullable', 'integer'],
                'dependentes.*._delete' => ['nullable', 'in:0,1'],
                'dependentes.*.nome' => ['nullable', 'string', 'max:255'],
                'dependentes.*.parentesco' => ['nullable', 'string', 'max:100'],
                'dependentes.*.nascimento' => ['nullable', 'date'],
                'dependentes.*.rg_num' => ['nullable', 'string', 'max:50'],
                'dependentes.*.rg_expedicao' => ['nullable', 'date'],
                'dependentes.*.cpf' => ['nullable', 'string', 'max:20'],
                'dependentes.*.certidao_tipo' => ['nullable', 'string', 'max:50', Rule::in(config('recad.certidao_tipo'))],
                'dependentes.*.sexo' => ['nullable', 'string', 'max:50', Rule::in(config('recad.sexo'))],
                'dependentes.*.tipo_dependente' => ['nullable', 'string', 'max:50', Rule::in(config('recad.dependente_tipo'))],
            ];
        }

        return $request->validate($rules);
    }

    private function uppercaseTextInputs(Request $request): void
    {
        $upper = function ($v) {
            if ($v === null) return null;
            if (!is_string($v)) return $v;
            $v = trim($v);
            if ($v === '') return '';
            if (function_exists('mb_strtoupper')) {
                return mb_strtoupper($v, 'UTF-8');
            }
            return strtoupper($v);
        };

        // Scalar fields: free-text only.
        // Do NOT uppercase enum-like fields that are validated with Rule::in(config('recad.*')),
        // otherwise the submitted value will not match the canonical option labels.
        $keys = [
            'nome','pai','mae','conjuge_nome','naturalidade','naturalidade_uf','nacionalidade',
            'curso','pos_graduacao','pos_curso',
            'endereco','numero','bairro','complemento','cep','cidade','cidade_uf','fone_fixo','celular','plano_saude',
            'rg_num','rg_uf','cpf','id_prof_num','id_prof_tipo','id_prof_uf','cnh_num','cnh_categoria','cnh_uf',
            'ctps_num','ctps_serie','titulo_eleitor_num','titulo_zona','titulo_secao','reservista_num','reservista_categoria',
            'reservista_uf','pis_pasep',
            'certidao_registro_num','certidao_livro','certidao_folha','certidao_matricula',
            'portaria_num','doe_num','cargo_funcao','orgao_origem',
            'banco_num','agencia_num','conta_corrente_num',
        ];

        $merge = [];
        foreach ($keys as $k) {
            if ($request->has($k)) {
                // Keep emails as-is (we do not uppercase).
                if ($k === 'email') continue;
                $merge[$k] = $upper($request->input($k));
            }
        }

        // Nested arrays.
        $contatos = $request->input('contatos_emergencia', []);
        if (is_array($contatos)) {
            foreach ($contatos as $i => $c) {
                if (!is_array($c)) continue;
                foreach (['nome','parentesco'] as $f) {
                    if (array_key_exists($f, $c)) $contatos[$i][$f] = $upper($c[$f]);
                }
            }
            $merge['contatos_emergencia'] = $contatos;
        }

        $dependentes = $request->input('dependentes', []);
        if (is_array($dependentes)) {
            foreach ($dependentes as $i => $d) {
                if (!is_array($d)) continue;
                // Keep radio/select fields as-is: certidao_tipo, sexo, tipo_dependente.
                foreach (['nome','parentesco','rg_num','cpf'] as $f) {
                    if (array_key_exists($f, $d)) $dependentes[$i][$f] = $upper($d[$f]);
                }
            }
            $merge['dependentes'] = $dependentes;
        }

        if (!empty($merge)) {
            $request->merge($merge);
        }
    }
}
