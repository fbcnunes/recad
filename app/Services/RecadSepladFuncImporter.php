<?php

namespace App\Services;

use App\Models\CertidaoServidor;
use App\Models\ContaBancaria;
use App\Models\DocumentoPessoal;
use App\Models\Servidor;
use App\Models\Vinculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\Recad\Format;

class RecadSepladFuncImporter
{
    /**
     * Importa TSV exportado do Recad (ISO-8859-1, CRLF, 60 colunas).
     *
     * Regra para conflitos por matricula:
     * - se houver mais de um valor nao-vazio diferente para o mesmo campo -> NULL.
     * - valores vazios nao contam como conflito.
     */
    public function import(string $path, bool $dryRun = false, ?int $limit = null, ?callable $log = null): array
    {
        $log ??= static function (string $msg): void {};

        if (!is_file($path)) {
            throw new \RuntimeException("Arquivo nao encontrado: {$path}");
        }

        $fh = new \SplFileObject($path, 'r');
        $fh->setFlags(\SplFileObject::DROP_NEW_LINE);

        $headerLine = $this->readLineUtf8($fh);
        if ($headerLine === null) {
            throw new \RuntimeException('Arquivo vazio.');
        }

        $header = $this->splitTsv($headerLine);
        if (count($header) !== 60) {
            throw new \RuntimeException('Header inesperado: ' . count($header) . ' colunas (esperado 60).');
        }

        $groups = [];
        $row = 1;
        $skipped = 0;

        while (!$fh->eof()) {
            $row++;
            $line = $this->readLineUtf8($fh);
            if ($line === null) {
                break;
            }
            if (trim($line) === '') {
                continue;
            }

            $cols = $this->splitTsv($line);
            if (count($cols) !== 60) {
                $skipped++;
                $log("Linha {$row}: {$skipped} ignorada (colunas=" . count($cols) . ').');
                continue;
            }

            $matricula = Format::normalizeMatricula($cols[4] ?? '');
            if ($matricula === null) {
                $skipped++;
                continue;
            }

            $groups[$matricula][] = $this->mapRow($cols);

            if ($limit !== null && count($groups) >= $limit) {
                break;
            }
        }

        $summary = [
            'matriculas' => count($groups),
            'dry_run' => $dryRun,
            'skipped_lines' => $skipped,
            'created' => 0,
            'updated' => 0,
            'conflicts' => 0,
            'nulled_duplicate_cpf' => 0,
            'nulled_duplicate_email' => 0,
            'nulled_conflict_fields' => 0,
        ];

        // Merge per matricula first to detect per-record conflicts.
        $mergedByMatricula = [];
        foreach ($groups as $matricula => $rows) {
            $merged = $this->mergeRows($rows);
            $merged['matricula'] = $matricula;
            $summary['conflicts'] += (int) ($merged['_conflicts'] ?? 0);
            $summary['nulled_conflict_fields'] += (int) ($merged['_conflicts'] ?? 0);
            $mergedByMatricula[$matricula] = $merged;
        }

        // Enforce global uniqueness for cpf/email across the import file to avoid unique constraint failures.
        $cpfCount = [];
        $emailCount = [];
        foreach ($mergedByMatricula as $m => $merged) {
            if (!empty($merged['cpf'])) {
                $cpfCount[$merged['cpf']] = ($cpfCount[$merged['cpf']] ?? 0) + 1;
            }
            if (!empty($merged['email'])) {
                $emailCount[$merged['email']] = ($emailCount[$merged['email']] ?? 0) + 1;
            }
        }

        foreach ($mergedByMatricula as $matricula => $merged) {
            if (!empty($merged['cpf']) && ($cpfCount[$merged['cpf']] ?? 0) > 1) {
                $merged['cpf'] = null;
                $summary['nulled_duplicate_cpf']++;
            }
            if (!empty($merged['email']) && ($emailCount[$merged['email']] ?? 0) > 1) {
                $merged['email'] = null;
                $summary['nulled_duplicate_email']++;
            }

            if (!$merged['nome']) {
                $log("Matricula {$matricula}: ignorada (nome vazio).");
                continue;
            }

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use (&$summary, $merged, $log) {
                $servidor = Servidor::where('matricula', $merged['matricula'])->first();
                $creating = false;
                if (!$servidor) {
                    $creating = true;
                    $servidor = new Servidor(['matricula' => $merged['matricula']]);
                }

                // Avoid unique constraint failures against existing data.
                if (!empty($merged['email'])) {
                    $emailTaken = Servidor::where('email', $merged['email'])
                        ->when(!$creating, fn ($q) => $q->where('id', '!=', $servidor->id))
                        ->exists();
                    if ($emailTaken) {
                        $merged['email'] = null;
                    }
                }

                $servidorData = array_filter([
                    'nome' => $merged['nome'],
                    'pai' => $merged['pai'],
                    'mae' => $merged['mae'],
                    'data_nascimento' => $merged['data_nascimento'],
                    'estado_civil' => $merged['estado_civil'],
                    'naturalidade' => $merged['naturalidade'],
                    'naturalidade_uf' => $merged['naturalidade_uf'],
                    'escolaridade' => $merged['escolaridade'],
                    'sexo' => $merged['sexo'],
                    'tipo_sanguineo' => $merged['tipo_sanguineo'],
                    'fator_rh' => $merged['fator_rh'],
                    'raca_cor' => $merged['raca_cor'],
                    'endereco' => $merged['endereco'],
                    'numero' => $merged['numero'],
                    'complemento' => $merged['complemento'],
                    'bairro' => $merged['bairro'],
                    'cidade' => $merged['cidade'],
                    'cidade_uf' => $merged['cidade_uf'],
                    'cep' => $merged['cep'],
                    'fone_fixo' => $merged['fone_fixo'],
                    'celular' => $merged['celular'],
                    'email' => $merged['email'],
                ], static fn ($v) => $v !== null);

                $servidor->fill($servidorData);
                $servidor->save();

                if (!empty($merged['cpf'])) {
                    $cpfTaken = DocumentoPessoal::where('cpf', $merged['cpf'])
                        ->where('servidor_id', '!=', $servidor->id)
                        ->exists();
                    if ($cpfTaken) {
                        $merged['cpf'] = null;
                    }
                }

                $docData = array_filter([
                    'rg_num' => $merged['rg_num'],
                    'rg_uf' => $merged['rg_uf'],
                    'cpf' => $merged['cpf'],
                    'ctps_num' => $merged['ctps_num'],
                    'ctps_serie' => $merged['ctps_serie'],
                    'titulo_eleitor_num' => $merged['titulo_eleitor_num'],
                    'titulo_zona' => $merged['titulo_zona'],
                    'titulo_secao' => $merged['titulo_secao'],
                    'reservista_num' => $merged['docto_militar_num'],
                    'reservista_categoria' => $merged['docto_militar_cat'],
                    'cnh_num' => $merged['cnh_num'],
                    'cnh_categoria' => $merged['cnh_cat'],
                    'cnh_validade' => $merged['cnh_validade'],
                    'cnh_uf' => $merged['cnh_uf'],
                    'pis_pasep' => $merged['pis_pasep'],
                    'id_prof_num' => $merged['ident_prof_num'],
                    'id_prof_tipo' => $merged['ident_prof_tipo'],
                    'id_prof_uf' => $merged['ident_prof_uf'],
                ], static fn ($v) => $v !== null);

                if (!empty($docData)) {
                    DocumentoPessoal::where('servidor_id', $servidor->id)->orderBy('id')->skip(1)->delete();
                    DocumentoPessoal::updateOrCreate(
                        ['servidor_id' => $servidor->id],
                        $docData
                    );
                }

                $certData = array_filter([
                    'registro_num' => $merged['certidao_num'],
                    'livro' => $merged['certidao_livro'],
                    'folha' => $merged['certidao_folha'],
                ], static fn ($v) => $v !== null);

                if (!empty($certData)) {
                    CertidaoServidor::where('servidor_id', $servidor->id)->orderBy('id')->skip(1)->delete();
                    CertidaoServidor::updateOrCreate(
                        ['servidor_id' => $servidor->id],
                        $certData
                    );
                }

                $vincData = array_filter([
                    'forma_ingresso' => $merged['tipo_vinculo'],
                    'cargo_funcao' => $merged['cargo_funcao'],
                    'orgao_origem' => $merged['sigla_orgao'],
                ], static fn ($v) => $v !== null);

                if (!empty($vincData)) {
                    Vinculo::where('servidor_id', $servidor->id)->orderBy('id')->skip(1)->delete();
                    Vinculo::updateOrCreate(
                        ['servidor_id' => $servidor->id],
                        $vincData
                    );
                }

                // O arquivo nao tem dados bancarios; mantem a tabela como esta.
                // Se no futuro vierem campos de banco, adicionar aqui.

                if ($creating) {
                    $summary['created']++;
                } else {
                    $summary['updated']++;
                }
            });
        }

        return $summary;
    }

    private function readLineUtf8(\SplFileObject $fh): ?string
    {
        $line = $fh->fgets();
        if ($line === false) {
            return null;
        }
        // Remove CRLF and convert encoding.
        $line = str_replace("\r", '', $line);
        $out = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $line);
        return $out === false ? $line : $out;
    }

    private function splitTsv(string $line): array
    {
        return explode("\t", $line);
    }

    private function mapRow(array $c): array
    {
        $sexo = trim((string) ($c[6] ?? ''));
        $sexo = $sexo === 'M' ? 'Masculino' : ($sexo === 'F' ? 'Feminino' : null);

        $raca = trim((string) ($c[7] ?? ''));
        $raca = preg_replace('/\\D+/', '', $raca ?? '');
        $raca = $this->mapRaca($raca);

        [$tipoSang, $rh] = $this->parseSangue((string) ($c[14] ?? ''));

        $fone = trim((string) ($c[16] ?? ''));
        $ramal = trim((string) ($c[17] ?? ''));
        $foneFixo = $fone !== '' ? ($ramal !== '' ? "{$fone} ramal {$ramal}" : $fone) : null;

        $cargoNome = $this->clean((string) ($c[57] ?? ''));
        $funcNome = $this->clean((string) ($c[59] ?? ''));
        $cargoFuncao = null;
        if ($cargoNome && $funcNome) {
            $cargoFuncao = "{$cargoNome} - {$funcNome}";
        } elseif ($cargoNome) {
            $cargoFuncao = $cargoNome;
        } elseif ($funcNome) {
            $cargoFuncao = $funcNome;
        }

        return [
            // servidores
            'sigla_orgao' => $this->clean((string) ($c[3] ?? '')),
            'nome' => $this->clean((string) ($c[5] ?? '')),
            'sexo' => $sexo,
            'raca_cor' => $raca,
            'data_nascimento' => $this->parseDateBr((string) ($c[8] ?? '')),
            'naturalidade' => $this->clean((string) ($c[9] ?? '')),
            'naturalidade_uf' => $this->cleanUf((string) ($c[10] ?? '')),
            'estado_civil' => $this->clean((string) ($c[11] ?? '')),
            'escolaridade' => $this->mapEscolaridade((string) ($c[12] ?? '')),
            'email' => $this->cleanLowerEmail((string) ($c[15] ?? '')),
            'fone_fixo' => $foneFixo,
            'celular' => $this->clean((string) ($c[18] ?? '')),
            'endereco' => $this->clean((string) ($c[19] ?? '')),
            'numero' => $this->clean((string) ($c[20] ?? '')),
            'complemento' => $this->clean((string) ($c[21] ?? '')),
            'bairro' => $this->clean((string) ($c[22] ?? '')),
            'cidade' => $this->clean((string) ($c[23] ?? '')),
            'cidade_uf' => $this->cleanUf((string) ($c[24] ?? '')),
            'cep' => $this->cleanCep((string) ($c[25] ?? '')),
            'pai' => $this->clean((string) ($c[26] ?? '')),
            'mae' => $this->clean((string) ($c[27] ?? '')),
            'tipo_sanguineo' => $tipoSang,
            'fator_rh' => $rh,

            // documentos_pessoais
            'cpf' => $this->cleanDigits((string) ($c[13] ?? ''), 11),
            'rg_num' => $this->clean((string) ($c[33] ?? '')),
            'rg_uf' => $this->cleanUf((string) ($c[35] ?? '')),
            'ctps_num' => $this->clean((string) ($c[36] ?? '')),
            'ctps_serie' => $this->clean((string) ($c[37] ?? '')),
            'titulo_eleitor_num' => $this->clean((string) ($c[39] ?? '')),
            'titulo_zona' => $this->clean((string) ($c[40] ?? '')),
            'titulo_secao' => $this->clean((string) ($c[41] ?? '')),
            'docto_militar_num' => $this->clean((string) ($c[43] ?? '')),
            'docto_militar_cat' => $this->clean((string) ($c[45] ?? '')),
            'cnh_num' => $this->clean((string) ($c[46] ?? '')),
            'cnh_cat' => $this->clean((string) ($c[47] ?? '')),
            'cnh_validade' => $this->parseDateBr((string) ($c[48] ?? '')),
            'cnh_uf' => $this->cleanUf((string) ($c[49] ?? '')),
            'pis_pasep' => $this->clean((string) ($c[50] ?? '')),

            'ident_prof_num' => $this->clean((string) ($c[52] ?? '')),
            'ident_prof_tipo' => $this->clean((string) ($c[53] ?? '')),
            'ident_prof_uf' => null,

            // certidao
            'certidao_num' => $this->clean((string) ($c[29] ?? '')),
            'certidao_livro' => $this->clean((string) ($c[30] ?? '')),
            'certidao_folha' => $this->clean((string) ($c[31] ?? '')),

            // vinculo
            'tipo_vinculo' => $this->clean((string) ($c[55] ?? '')),
            'cargo_funcao' => $cargoFuncao,
        ];
    }

    private function mergeRows(array $rows): array
    {
        $keys = array_keys($rows[0] ?? []);
        $out = [];
        $conflicts = 0;

        foreach ($keys as $key) {
            $values = [];
            foreach ($rows as $r) {
                $v = $r[$key] ?? null;
                if ($v === null) {
                    continue;
                }
                if (is_string($v) && trim($v) === '') {
                    continue;
                }
                $values[] = $v;
            }

            $uniq = $this->uniqueValues($values);
            if (count($uniq) === 0) {
                $out[$key] = null;
            } elseif (count($uniq) === 1) {
                $out[$key] = $uniq[0];
            } else {
                $out[$key] = null;
                $conflicts++;
            }
        }

        $out['_conflicts'] = $conflicts;
        return $out;
    }

    private function uniqueValues(array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            $found = false;
            foreach ($out as $o) {
                if ($o === $v) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $out[] = $v;
            }
        }
        return $out;
    }

    private function clean(string $v): ?string
    {
        $v = trim($v);
        return $v === '' ? null : $v;
    }

    private function cleanLowerEmail(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        return Str::lower($v);
    }

    private function cleanDigits(string $v, int $len): ?string
    {
        $v = preg_replace('/\\D+/', '', $v);
        if (!$v) {
            return null;
        }
        return strlen($v) === $len ? $v : $v;
    }

    private function cleanUf(string $v): ?string
    {
        $v = strtoupper(trim($v));
        if ($v === '') {
            return null;
        }
        return strlen($v) === 2 ? $v : $v;
    }

    private function cleanCep(string $v): ?string
    {
        $v = preg_replace('/\\D+/', '', $v);
        if (!$v) {
            return null;
        }
        if (strlen($v) === 8) {
            return $v;
        }
        return $v;
    }

    private function parseDateBr(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        $dt = \DateTime::createFromFormat('j/n/Y', $v) ?: \DateTime::createFromFormat('d/m/Y', $v);
        if (!$dt) {
            return null;
        }
        return $dt->format('Y-m-d');
    }

    private function mapEscolaridade(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        if (str_starts_with($v, 'Ens.Superior')) {
            return 'Superior';
        }
        if (str_starts_with($v, 'Ens.Medio')) {
            return 'Médio';
        }
        if (str_starts_with($v, 'Ens.Fundam')) {
            return 'Fundamental';
        }
        return null;
    }

    private function parseSangue(string $v): array
    {
        $v = strtoupper(trim($v));
        if ($v === '') {
            return [null, null];
        }
        if (preg_match('/^(A|B|AB|O)\\s*([+-])$/', $v, $m)) {
            return [$m[1], $m[2]];
        }
        return [null, null];
    }

    private function mapRaca(string $code): ?string
    {
        // 1 = Indigena, 2 = Branca, 4 = Negra (Preta), 6 = Amarela, 8 = Parda, 9 = Nao Informada
        return match ($code) {
            '1' => 'Indígena',
            '2' => 'Branca',
            '4' => 'Preta',
            '6' => 'Amarela',
            '8' => 'Parda',
            '9' => null,
            default => null,
        };
    }
}
