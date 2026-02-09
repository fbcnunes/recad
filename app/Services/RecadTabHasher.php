<?php

namespace App\Services;

use App\Models\Servidor;

class RecadTabHasher
{
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

    public function hashes(Servidor $servidor): array
    {
        $doc = $servidor->documentoPessoal;
        $cert = $servidor->certidaoServidor;
        $vinc = $servidor->vinculo;
        $conta = $servidor->contaBancaria;

        $out = [];

        $out['pessoais'] = $this->hash([
            'nome' => $servidor->nome,
            'pai' => $servidor->pai,
            'mae' => $servidor->mae,
            'data_nascimento' => $this->date($servidor->data_nascimento),
            'estado_civil' => $servidor->estado_civil,
            'conjuge_nome' => $servidor->conjuge_nome,
            'naturalidade' => $servidor->naturalidade,
            'naturalidade_uf' => $servidor->naturalidade_uf,
            'nacionalidade' => $servidor->nacionalidade,
            'escolaridade' => $servidor->escolaridade,
            'curso' => $servidor->curso,
            'pos_graduacao' => $servidor->pos_graduacao,
            'pos_curso' => $servidor->pos_curso,
            'pos_inicio' => $this->date($servidor->pos_inicio),
            'pos_fim' => $this->date($servidor->pos_fim),
            'pos_carga_horaria' => $servidor->pos_carga_horaria,
            'sexo' => $servidor->sexo,
            'tipo_sanguineo' => $servidor->tipo_sanguineo,
            'fator_rh' => $servidor->fator_rh,
            'raca_cor' => $servidor->raca_cor,
        ]);

        $out['endereco'] = $this->hash([
            'endereco' => $servidor->endereco,
            'numero' => $servidor->numero,
            'bairro' => $servidor->bairro,
            'complemento' => $servidor->complemento,
            'cep' => $servidor->cep,
            'cidade' => $servidor->cidade,
            'cidade_uf' => $servidor->cidade_uf,
            'fone_fixo' => $servidor->fone_fixo,
            'celular' => $servidor->celular,
            'email' => $servidor->email,
            'plano_saude' => $servidor->plano_saude,
        ]);

        $out['documentos'] = $this->hash([
            'rg_num' => $doc?->rg_num,
            'rg_uf' => $doc?->rg_uf,
            'rg_expedicao' => $this->date($doc?->rg_expedicao),
            'cpf' => $doc?->cpf,
            'id_prof_num' => $doc?->id_prof_num,
            'id_prof_tipo' => $doc?->id_prof_tipo,
            'id_prof_uf' => $doc?->id_prof_uf,
            'cnh_num' => $doc?->cnh_num,
            'cnh_categoria' => $doc?->cnh_categoria,
            'cnh_validade' => $this->date($doc?->cnh_validade),
            'cnh_uf' => $doc?->cnh_uf,
            'ctps_num' => $doc?->ctps_num,
            'ctps_serie' => $doc?->ctps_serie,
            'ctps_expedicao' => $this->date($doc?->ctps_expedicao),
            'titulo_eleitor_num' => $doc?->titulo_eleitor_num,
            'titulo_zona' => $doc?->titulo_zona,
            'titulo_secao' => $doc?->titulo_secao,
            'reservista_num' => $doc?->reservista_num,
            'reservista_categoria' => $doc?->reservista_categoria,
            'reservista_uf' => $doc?->reservista_uf,
            'pis_pasep' => $doc?->pis_pasep,
        ]);

        $out['certidao'] = $this->hash([
            'tipo' => $cert?->tipo,
            'registro_num' => $cert?->registro_num,
            'livro' => $cert?->livro,
            'folha' => $cert?->folha,
            'matricula' => $cert?->matricula,
        ]);

        $out['ingresso'] = $this->hash([
            'forma_ingresso' => $vinc?->forma_ingresso,
            'data_ingresso' => $this->date($vinc?->data_ingresso),
            'nomeacao_cessao_data' => $this->date($vinc?->nomeacao_cessao_data),
            'portaria_num' => $vinc?->portaria_num,
            'doe_num' => $vinc?->doe_num,
            'doe_publicacao_data' => $this->date($vinc?->doe_publicacao_data),
            'cargo_funcao' => $vinc?->cargo_funcao,
            'orgao_origem' => $vinc?->orgao_origem,
        ]);

        $out['banco'] = $this->hash([
            'banco_num' => $conta?->banco_num,
            'agencia_num' => $conta?->agencia_num,
            'conta_corrente_num' => $conta?->conta_corrente_num,
        ]);

        $contatos = $servidor->contatosEmergencia
            ->map(fn ($c) => [
                'nome' => $c->nome,
                'celular' => $c->celular,
                'parentesco' => $c->parentesco,
            ])
            ->sortBy(fn ($c) => ($c['nome'] ?? '') . '|' . ($c['celular'] ?? '') . '|' . ($c['parentesco'] ?? ''))
            ->values()
            ->all();

        $out['emergencia'] = $this->hash($contatos);

        $dependentes = $servidor->dependentes
            ->map(fn ($d) => [
                'nome' => $d->nome,
                'parentesco' => $d->parentesco,
                'nascimento' => $this->date($d->nascimento),
                'rg_num' => $d->rg_num,
                'rg_expedicao' => $this->date($d->rg_expedicao),
                'cpf' => $d->cpf,
                'certidao_tipo' => $d->certidao_tipo,
                'sexo' => $d->sexo,
                'tipo_dependente' => $d->tipo_dependente,
            ])
            ->sortBy(fn ($d) => ($d['nome'] ?? '') . '|' . ($d['nascimento'] ?? ''))
            ->values()
            ->all();

        $out['dependentes'] = $this->hash($dependentes);

        return $out;
    }

    private function date($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        return (string) $value;
    }

    private function hash($data): string
    {
        $normalized = $this->normalize($data);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha256', $json ?: '');
    }

    private function normalize($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->normalize($v);
            }
            return $out;
        }
        if (is_string($value)) {
            $v = trim($value);
            return $v === '' ? null : $v;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        return $value;
    }
}

