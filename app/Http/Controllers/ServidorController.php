<?php

namespace App\Http\Controllers;

use App\Models\CertidaoServidor;
use App\Models\ContaBancaria;
use App\Models\ContatoEmergencia;
use App\Models\Dependente;
use App\Models\DocumentoPessoal;
use App\Models\Servidor;
use App\Models\Vinculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServidorController extends Controller
{
    public function showSelf(Request $request)
    {
        $matricula = $request->session()->get('ldap_pager');
        $edit = $request->boolean('edit');

        if (!$matricula) {
            return view('servidores.profile', [
                'servidor' => null,
                'edit' => false,
                'matricula' => null,
                'notFound' => 'Matrícula não encontrada no AD. Procure a DTI/CORI.',
                'createMode' => false,
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
            ])
            ->where('matricula', $matricula)
            ->first();

        if (!$servidor) {
            return view('servidores.profile', [
                'servidor' => null,
                'edit' => true,
                'matricula' => $matricula,
                'notFound' => null,
                'createMode' => true,
            ]);
        }

        return view('servidores.profile', [
            'servidor' => $servidor,
            'edit' => $edit,
            'matricula' => $matricula,
            'notFound' => null,
            'createMode' => false,
        ]);
    }

    public function updateSelf(Request $request)
    {
        $matricula = $request->session()->get('ldap_pager');
        if (!$matricula) {
            return redirect()->route('servidores.self')
                ->withErrors(['matricula' => 'Matrícula não encontrada. Procure a DTI/CORI.']);
        }

        $tab = (string) $request->input('_active_tab', 'pessoais');
        $allowedTabs = [
            'pessoais',
            'endereco',
            'documentos',
            'certidao',
            'ingresso',
            'banco',
            'emergencia',
            'dependentes',
        ];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'pessoais';
        }

        $servidor = Servidor::where('matricula', $matricula)->first();
        $creating = false;
        if (!$servidor) {
            $creating = true;
            $servidor = new Servidor(['matricula' => $matricula]);
        }

        $documento = $servidor->documentoPessoal ?? null;
        $data = $this->validateData($request, $servidor, $documento);

        $servidorData = $this->only($data, [
            'nome', 'pai', 'mae', 'data_nascimento', 'estado_civil', 'conjuge_nome',
            'naturalidade', 'naturalidade_uf', 'nacionalidade', 'escolaridade', 'curso',
            'pos_graduacao', 'pos_curso', 'pos_inicio', 'pos_fim', 'pos_carga_horaria', 'sexo',
            'tipo_sanguineo', 'fator_rh', 'raca_cor', 'endereco', 'numero', 'bairro', 'complemento',
            'cep', 'cidade', 'cidade_uf', 'fone_fixo', 'celular', 'email', 'plano_saude',
        ]);

        $documentoData = $this->only($data, [
            'rg_num', 'rg_uf', 'rg_expedicao', 'cpf', 'id_prof_num', 'id_prof_tipo', 'id_prof_uf',
            'cnh_num', 'cnh_categoria', 'cnh_validade', 'cnh_uf', 'ctps_num', 'ctps_serie',
            'ctps_expedicao', 'titulo_eleitor_num', 'titulo_zona', 'titulo_secao', 'reservista_num',
            'reservista_categoria', 'reservista_uf', 'pis_pasep',
        ]);

        $certidaoData = [
            'tipo' => $data['certidao_tipo'] ?? null,
            'registro_num' => $data['certidao_registro_num'] ?? null,
            'livro' => $data['certidao_livro'] ?? null,
            'folha' => $data['certidao_folha'] ?? null,
            'matricula' => $data['certidao_matricula'] ?? null,
        ];

        $vinculoData = $this->only($data, [
            'forma_ingresso', 'data_ingresso', 'nomeacao_cessao_data', 'portaria_num', 'doe_num',
            'doe_publicacao_data', 'cargo_funcao', 'orgao_origem',
        ]);

        $contaData = $this->only($data, [
            'banco_num', 'agencia_num', 'conta_corrente_num',
        ]);

        DB::transaction(function () use (
            $servidor,
            $creating,
            $documento,
            $servidorData,
            $documentoData,
            $certidaoData,
            $vinculoData,
            $contaData,
            $data
        ) {
            if ($creating) {
                $servidor->fill($servidorData);
                $servidor->save();
            } else {
                $servidor->update($servidorData);
            }

            if ($this->hasAny($documentoData)) {
                if ($documento) {
                    $documento->update($documentoData);
                } else {
                    DocumentoPessoal::create(array_merge($documentoData, [
                        'servidor_id' => $servidor->id,
                    ]));
                }
            }

            if ($this->hasAny($certidaoData)) {
                $servidor->certidaoServidor()
                    ->updateOrCreate(['servidor_id' => $servidor->id], $certidaoData);
            }

            if ($this->hasAny($vinculoData)) {
                $servidor->vinculo()
                    ->updateOrCreate(['servidor_id' => $servidor->id], $vinculoData);
            }

            if ($this->hasAny($contaData)) {
                $servidor->contaBancaria()
                    ->updateOrCreate(['servidor_id' => $servidor->id], $contaData);
            }

            $servidor->contatosEmergencia()->delete();
            $contatos = $data['contatos_emergencia'] ?? [];
            foreach ($contatos as $contato) {
                if ($this->hasAny($contato)) {
                    ContatoEmergencia::create(array_merge($contato, [
                        'servidor_id' => $servidor->id,
                    ]));
                }
            }

            $servidor->dependentes()->delete();
            $dependentes = $data['dependentes'] ?? [];
            foreach ($dependentes as $dependente) {
                if ($this->hasAny($dependente)) {
                    Dependente::create(array_merge($dependente, [
                        'servidor_id' => $servidor->id,
                    ]));
                }
            }
        });

        return redirect()
            ->to(route('servidores.self') . '#' . $tab)
            ->with('status', 'Dados atualizados com sucesso.');
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
}
