<?php

namespace App\Services;

use App\Models\Servidor;
use App\Models\ServidorConfirmacao;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ServidorConfirmacaoService
{
    /**
     * Canonical list of required tabs for recadastramento.
     * Keep in sync with UI and controller.
     */
    public const TABS = [
        'pessoais',
        'endereco',
        'documentos',
        'certidao',
        'ingresso',
        'banco',
        'emergencia',
        'dependentes',
    ];

    public static function labels(): array
    {
        return [
            'pessoais' => 'Dados pessoais',
            'endereco' => 'Endereco',
            'documentos' => 'Documentacao',
            'certidao' => 'Certidao',
            'ingresso' => 'Ingresso',
            'banco' => 'Banco',
            'emergencia' => 'Emergencia',
            'dependentes' => 'Dependentes',
        ];
    }

    public static function isValidTab(string $tab): bool
    {
        return in_array($tab, self::TABS, true);
    }

    public function hashFor(Servidor $servidor, string $tab): string
    {
        $payload = $this->payloadFor($servidor, $tab);
        $payload = $this->deepSort($payload);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha256', $json ?: '');
    }

    /**
     * Removes confirmations whose stored snapshot is stale.
     * Returns list of invalidated tabs.
     */
    public function syncStaleConfirmacoes(Servidor $servidor): array
    {
        if (!$servidor->exists) return [];

        $servidor->loadMissing([
            'documentoPessoal',
            'certidaoServidor',
            'vinculo',
            'contaBancaria',
            'contatosEmergencia',
            'dependentes',
            'confirmacoes',
        ]);

        $invalidated = [];

        foreach ($servidor->confirmacoes as $conf) {
            $tab = (string) $conf->aba;
            if (!self::isValidTab($tab)) {
                $conf->delete();
                continue;
            }

            $current = $this->hashFor($servidor, $tab);
            if (!hash_equals((string) $conf->hash_snapshot, $current)) {
                $invalidated[] = $tab;
                $conf->delete();
            }
        }

        if (!empty($invalidated) && $servidor->recadastramento_concluido_em) {
            $servidor->recadastramento_concluido_em = null;
            $servidor->recadastramento_concluido_por_user_id = null;
            $servidor->save();
        }

        return array_values(array_unique($invalidated));
    }

    public function confirmTab(Servidor $servidor, string $tab, ?int $userId): void
    {
        if (!$servidor->exists) {
            throw new \RuntimeException('Servidor precisa estar salvo para confirmar.');
        }
        if (!self::isValidTab($tab)) {
            throw new \InvalidArgumentException('Aba invalida.');
        }

        $servidor->loadMissing([
            'documentoPessoal',
            'certidaoServidor',
            'vinculo',
            'contaBancaria',
            'contatosEmergencia',
            'dependentes',
        ]);

        $hash = $this->hashFor($servidor, $tab);

        DB::transaction(function () use ($servidor, $tab, $hash, $userId) {
            ServidorConfirmacao::query()->updateOrCreate(
                ['servidor_id' => $servidor->id, 'aba' => $tab],
                [
                    'hash_snapshot' => $hash,
                    'confirmado_em' => now(),
                    'confirmado_por_user_id' => $userId,
                ]
            );
        });
    }

    public function unconfirmTab(Servidor $servidor, string $tab): void
    {
        if (!$servidor->exists) return;
        if (!self::isValidTab($tab)) return;

        DB::transaction(function () use ($servidor, $tab) {
            ServidorConfirmacao::query()
                ->where('servidor_id', $servidor->id)
                ->where('aba', $tab)
                ->delete();
        });

        if ($servidor->recadastramento_concluido_em) {
            $servidor->recadastramento_concluido_em = null;
            $servidor->recadastramento_concluido_por_user_id = null;
            $servidor->save();
        }
    }

    public function allTabsConfirmed(Servidor $servidor): bool
    {
        if (!$servidor->exists) return false;
        $count = ServidorConfirmacao::query()
            ->where('servidor_id', $servidor->id)
            ->whereIn('aba', self::TABS)
            ->count();

        return $count === count(self::TABS);
    }

    private function payloadFor(Servidor $s, string $tab): array
    {
        $doc = $s->documentoPessoal;
        $cert = $s->certidaoServidor;
        $vinc = $s->vinculo;
        $conta = $s->contaBancaria;

        $norm = fn ($v) => $this->normalize($v);

        if ($tab === 'pessoais') {
            return [
                'nome' => $norm($s->nome),
                'pai' => $norm($s->pai),
                'mae' => $norm($s->mae),
                'data_nascimento' => $norm($s->data_nascimento),
                'estado_civil' => $norm($s->estado_civil),
                'conjuge_nome' => $norm($s->conjuge_nome),
                'naturalidade' => $norm($s->naturalidade),
                'naturalidade_uf' => $norm($s->naturalidade_uf),
                'nacionalidade' => $norm($s->nacionalidade),
                'escolaridade' => $norm($s->escolaridade),
                'curso' => $norm($s->curso),
                'pos_graduacao' => $norm($s->pos_graduacao),
                'pos_curso' => $norm($s->pos_curso),
                'pos_inicio' => $norm($s->pos_inicio),
                'pos_fim' => $norm($s->pos_fim),
                'pos_carga_horaria' => $s->pos_carga_horaria === null ? null : (int) $s->pos_carga_horaria,
                'sexo' => $norm($s->sexo),
                'tipo_sanguineo' => $norm($s->tipo_sanguineo),
                'fator_rh' => $norm($s->fator_rh),
                'raca_cor' => $norm($s->raca_cor),
            ];
        }

        if ($tab === 'endereco') {
            return [
                'endereco' => $norm($s->endereco),
                'numero' => $norm($s->numero),
                'bairro' => $norm($s->bairro),
                'complemento' => $norm($s->complemento),
                'cep' => $norm($s->cep),
                'cidade' => $norm($s->cidade),
                'cidade_uf' => $norm($s->cidade_uf),
                'fone_fixo' => $norm($s->fone_fixo),
                'celular' => $norm($s->celular),
                'email' => $norm($s->email),
                'plano_saude' => $norm($s->plano_saude),
            ];
        }

        if ($tab === 'documentos') {
            return [
                'rg_num' => $norm($doc?->rg_num),
                'rg_uf' => $norm($doc?->rg_uf),
                'rg_expedicao' => $norm($doc?->rg_expedicao),
                'cpf' => $norm($doc?->cpf),
                'id_prof_num' => $norm($doc?->id_prof_num),
                'id_prof_tipo' => $norm($doc?->id_prof_tipo),
                'id_prof_uf' => $norm($doc?->id_prof_uf),
                'cnh_num' => $norm($doc?->cnh_num),
                'cnh_categoria' => $norm($doc?->cnh_categoria),
                'cnh_validade' => $norm($doc?->cnh_validade),
                'cnh_uf' => $norm($doc?->cnh_uf),
                'ctps_num' => $norm($doc?->ctps_num),
                'ctps_serie' => $norm($doc?->ctps_serie),
                'ctps_expedicao' => $norm($doc?->ctps_expedicao),
                'titulo_eleitor_num' => $norm($doc?->titulo_eleitor_num),
                'titulo_zona' => $norm($doc?->titulo_zona),
                'titulo_secao' => $norm($doc?->titulo_secao),
                'reservista_num' => $norm($doc?->reservista_num),
                'reservista_categoria' => $norm($doc?->reservista_categoria),
                'reservista_uf' => $norm($doc?->reservista_uf),
                'pis_pasep' => $norm($doc?->pis_pasep),
            ];
        }

        if ($tab === 'certidao') {
            return [
                'tipo' => $norm($cert?->tipo),
                'registro_num' => $norm($cert?->registro_num),
                'livro' => $norm($cert?->livro),
                'folha' => $norm($cert?->folha),
                'matricula' => $norm($cert?->matricula),
            ];
        }

        if ($tab === 'ingresso') {
            return [
                'forma_ingresso' => $norm($vinc?->forma_ingresso),
                'data_ingresso' => $norm($vinc?->data_ingresso),
                'nomeacao_cessao_data' => $norm($vinc?->nomeacao_cessao_data),
                'portaria_num' => $norm($vinc?->portaria_num),
                'doe_num' => $norm($vinc?->doe_num),
                'doe_publicacao_data' => $norm($vinc?->doe_publicacao_data),
                'cargo_funcao' => $norm($vinc?->cargo_funcao),
                'orgao_origem' => $norm($vinc?->orgao_origem),
            ];
        }

        if ($tab === 'banco') {
            return [
                'banco_num' => $norm($conta?->banco_num),
                'agencia_num' => $norm($conta?->agencia_num),
                'conta_corrente_num' => $norm($conta?->conta_corrente_num),
            ];
        }

        if ($tab === 'emergencia') {
            $items = [];
            foreach ($s->contatosEmergencia ?? [] as $c) {
                $items[] = [
                    'nome' => $norm($c->nome),
                    'celular' => $norm($c->celular),
                    'parentesco' => $norm($c->parentesco),
                ];
            }
            $items = $this->sortList($items, ['nome', 'celular', 'parentesco']);
            return ['contatos' => $items];
        }

        if ($tab === 'dependentes') {
            $items = [];
            foreach ($s->dependentes ?? [] as $d) {
                $items[] = [
                    'nome' => $norm($d->nome),
                    'parentesco' => $norm($d->parentesco),
                    'nascimento' => $norm($d->nascimento),
                    'rg_num' => $norm($d->rg_num),
                    'rg_expedicao' => $norm($d->rg_expedicao),
                    'cpf' => $norm($d->cpf),
                    'certidao_tipo' => $norm($d->certidao_tipo),
                    'sexo' => $norm($d->sexo),
                    'tipo_dependente' => $norm($d->tipo_dependente),
                ];
            }
            $items = $this->sortList($items, ['nome', 'cpf', 'nascimento', 'parentesco']);
            return ['dependentes' => $items];
        }

        return [];
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            // Dates stored as "date" or "datetime" casts. Use a stable string.
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $v = trim($value);
            if ($v === '') return '';
            // Collapse whitespace to keep snapshots stable.
            $v = preg_replace('/\\s+/u', ' ', $v) ?? $v;
            return $v;
        }

        return $value;
    }

    private function deepSort(mixed $value): mixed
    {
        if (!is_array($value)) return $value;

        if (Arr::isList($value)) {
            return array_map(fn ($v) => $this->deepSort($v), $value);
        }

        ksort($value);
        foreach ($value as $k => $v) {
            $value[$k] = $this->deepSort($v);
        }
        return $value;
    }

    private function sortList(array $items, array $byKeys): array
    {
        usort($items, function ($a, $b) use ($byKeys) {
            foreach ($byKeys as $k) {
                $av = is_array($a) ? ($a[$k] ?? '') : '';
                $bv = is_array($b) ? ($b[$k] ?? '') : '';
                $av = is_string($av) ? $av : (string) ($av ?? '');
                $bv = is_string($bv) ? $bv : (string) ($bv ?? '');
                if ($av === $bv) continue;
                return $av < $bv ? -1 : 1;
            }
            return 0;
        });
        return $items;
    }
}

