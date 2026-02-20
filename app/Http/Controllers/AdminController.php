<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Servidor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalServidores = Servidor::query()->count();
        $totalConcluidos = Servidor::query()->whereNotNull('recadastramento_concluido_em')->count();
        $totalPendentes = $totalServidores - $totalConcluidos;
        $totalAdminsManuais = AdminUser::query()->count();

        return view('admin.dashboard', [
            'totalServidores' => $totalServidores,
            'totalConcluidos' => $totalConcluidos,
            'totalPendentes' => $totalPendentes,
            'totalAdminsManuais' => $totalAdminsManuais,
        ]);
    }

    public function concluidos(Request $request)
    {
        $query = $this->concluidosQueryFromRequest($request)
            ->with('concluidoPor:id,name,username')
            ->orderByDesc('recadastramento_concluido_em');

        $servidores = $query->paginate(25)->withQueryString();

        return view('admin.concluidos', [
            'servidores' => $servidores,
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'concluido_de' => (string) $request->query('concluido_de', ''),
                'concluido_ate' => (string) $request->query('concluido_ate', ''),
            ],
        ]);
    }

    public function exportConcluidosCsv(Request $request)
    {
        $rows = $this->concluidosQueryFromRequest($request)
            ->with([
                'documentoPessoal',
                'certidaoServidor',
                'vinculo',
                'contaBancaria',
                'contatosEmergencia',
                'dependentes',
                'concluidoPor:id,name,username',
            ])
            ->orderByDesc('recadastramento_concluido_em')
            ->get();

        $filename = 'concluidos_recad_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'servidor_id',
                'matricula',
                'nome',
                'pai',
                'mae',
                'data_nascimento',
                'estado_civil',
                'conjuge_nome',
                'naturalidade',
                'naturalidade_uf',
                'nacionalidade',
                'escolaridade',
                'curso',
                'pos_graduacao',
                'pos_curso',
                'pos_inicio',
                'pos_fim',
                'pos_carga_horaria',
                'sexo',
                'tipo_sanguineo',
                'fator_rh',
                'raca_cor',
                'endereco',
                'numero',
                'bairro',
                'complemento',
                'cep',
                'cidade',
                'cidade_uf',
                'fone_fixo',
                'email',
                'celular',
                'plano_saude',
                'doc_rg_num',
                'doc_rg_uf',
                'doc_rg_expedicao',
                'doc_cpf',
                'doc_id_prof_num',
                'doc_id_prof_tipo',
                'doc_id_prof_uf',
                'doc_cnh_num',
                'doc_cnh_categoria',
                'doc_cnh_validade',
                'doc_cnh_uf',
                'doc_ctps_num',
                'doc_ctps_serie',
                'doc_ctps_expedicao',
                'doc_titulo_eleitor_num',
                'doc_titulo_zona',
                'doc_titulo_secao',
                'doc_reservista_num',
                'doc_reservista_categoria',
                'doc_reservista_uf',
                'doc_pis_pasep',
                'certidao_tipo',
                'certidao_registro_num',
                'certidao_livro',
                'certidao_folha',
                'certidao_matricula',
                'vinculo_forma_ingresso',
                'vinculo_data_ingresso',
                'vinculo_nomeacao_cessao_data',
                'vinculo_portaria_num',
                'vinculo_doe_num',
                'vinculo_doe_publicacao_data',
                'vinculo_cargo_funcao',
                'vinculo_orgao_origem',
                'banco_num',
                'agencia_num',
                'conta_corrente_num',
                'contatos_emergencia_json',
                'dependentes_json',
                'concluido_em',
                'concluido_por_user_id',
                'concluido_por',
                'created_at',
                'updated_at',
            ], ';');

            foreach ($rows as $s) {
                $doc = $s->documentoPessoal;
                $cert = $s->certidaoServidor;
                $vinc = $s->vinculo;
                $conta = $s->contaBancaria;
                $contatos = $s->contatosEmergencia->map(function ($item) {
                    return [
                        'nome' => $item->nome,
                        'celular' => $item->celular,
                        'parentesco' => $item->parentesco,
                    ];
                })->values()->all();
                $dependentes = $s->dependentes->map(function ($item) {
                    return [
                        'nome' => $item->nome,
                        'parentesco' => $item->parentesco,
                        'nascimento' => $item->nascimento,
                        'rg_num' => $item->rg_num,
                        'rg_expedicao' => $item->rg_expedicao,
                        'cpf' => $item->cpf,
                        'certidao_tipo' => $item->certidao_tipo,
                        'sexo' => $item->sexo,
                        'tipo_dependente' => $item->tipo_dependente,
                    ];
                })->values()->all();

                fputcsv($out, [
                    $s->id,
                    $s->matricula,
                    $s->nome,
                    $s->pai,
                    $s->mae,
                    optional($s->data_nascimento)->format('Y-m-d'),
                    $s->estado_civil,
                    $s->conjuge_nome,
                    $s->naturalidade,
                    $s->naturalidade_uf,
                    $s->nacionalidade,
                    $s->escolaridade,
                    $s->curso,
                    $s->pos_graduacao,
                    $s->pos_curso,
                    optional($s->pos_inicio)->format('Y-m-d'),
                    optional($s->pos_fim)->format('Y-m-d'),
                    $s->pos_carga_horaria,
                    $s->sexo,
                    $s->tipo_sanguineo,
                    $s->fator_rh,
                    $s->raca_cor,
                    $s->endereco,
                    $s->numero,
                    $s->bairro,
                    $s->complemento,
                    $s->cep,
                    $s->cidade,
                    $s->cidade_uf,
                    $s->fone_fixo,
                    $s->email,
                    $s->celular,
                    $s->plano_saude,
                    $doc?->rg_num,
                    $doc?->rg_uf,
                    optional($doc?->rg_expedicao)->format('Y-m-d'),
                    $doc?->cpf,
                    $doc?->id_prof_num,
                    $doc?->id_prof_tipo,
                    $doc?->id_prof_uf,
                    $doc?->cnh_num,
                    $doc?->cnh_categoria,
                    optional($doc?->cnh_validade)->format('Y-m-d'),
                    $doc?->cnh_uf,
                    $doc?->ctps_num,
                    $doc?->ctps_serie,
                    optional($doc?->ctps_expedicao)->format('Y-m-d'),
                    $doc?->titulo_eleitor_num,
                    $doc?->titulo_zona,
                    $doc?->titulo_secao,
                    $doc?->reservista_num,
                    $doc?->reservista_categoria,
                    $doc?->reservista_uf,
                    $doc?->pis_pasep,
                    $cert?->tipo,
                    $cert?->registro_num,
                    $cert?->livro,
                    $cert?->folha,
                    $cert?->matricula,
                    $vinc?->forma_ingresso,
                    optional($vinc?->data_ingresso)->format('Y-m-d'),
                    optional($vinc?->nomeacao_cessao_data)->format('Y-m-d'),
                    $vinc?->portaria_num,
                    $vinc?->doe_num,
                    optional($vinc?->doe_publicacao_data)->format('Y-m-d'),
                    $vinc?->cargo_funcao,
                    $vinc?->orgao_origem,
                    $conta?->banco_num,
                    $conta?->agencia_num,
                    $conta?->conta_corrente_num,
                    json_encode($contatos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    json_encode($dependentes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    optional($s->recadastramento_concluido_em)->format('Y-m-d H:i:s'),
                    $s->recadastramento_concluido_por_user_id,
                    $s->concluidoPor?->username ?? $s->concluidoPor?->name,
                    optional($s->created_at)->format('Y-m-d H:i:s'),
                    optional($s->updated_at)->format('Y-m-d H:i:s'),
                ], ';');
            }

            fclose($out);
        }, $filename, $headers);
    }

    public function admins(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with(['adminGrant.grantedBy:id,name,username'])
            ->when($q !== '', function (Builder $builder) use ($q) {
                $builder->where(function (Builder $inner) use ($q) {
                    $inner->where('name', 'like', '%' . $q . '%')
                        ->orWhere('username', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.admins', [
            'users' => $users,
            'q' => $q,
            'configuredInitialAdmins' => User::configuredInitialAdminUsernames(),
        ]);
    }

    public function grantAdmin(User $user)
    {
        AdminUser::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['granted_by_user_id' => Auth::id()]
        );

        return back()->with('status', 'Administrador concedido para ' . ($user->username ?: $user->name) . '.');
    }

    public function revokeAdmin(User $user)
    {
        if ($user->isConfiguredInitialAdmin()) {
            return back()->withErrors([
                'admin' => 'Este usuário é administrador inicial via configuração e não pode ser revogado pela tela.',
            ]);
        }

        AdminUser::query()->where('user_id', $user->id)->delete();

        return back()->with('status', 'Administrador revogado para ' . ($user->username ?: $user->name) . '.');
    }

    private function concluidosQueryFromRequest(Request $request): Builder
    {
        $q = trim((string) $request->query('q', ''));
        $concluidoDe = (string) $request->query('concluido_de', '');
        $concluidoAte = (string) $request->query('concluido_ate', '');

        return Servidor::query()
            ->whereNotNull('recadastramento_concluido_em')
            ->when($q !== '', function (Builder $builder) use ($q) {
                $builder->where(function (Builder $inner) use ($q) {
                    $inner->where('nome', 'like', '%' . $q . '%')
                        ->orWhere('matricula', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%');
                });
            })
            ->when($concluidoDe !== '', function (Builder $builder) use ($concluidoDe) {
                $builder->whereDate('recadastramento_concluido_em', '>=', $concluidoDe);
            })
            ->when($concluidoAte !== '', function (Builder $builder) use ($concluidoAte) {
                $builder->whereDate('recadastramento_concluido_em', '<=', $concluidoAte);
            });
    }
}
