@php
    $doc = $servidor->documentoPessoal;
    $cert = $servidor->certidaoServidor;
    $vinc = $servidor->vinculo;
    $conta = $servidor->contaBancaria;
    $contatos = $servidor->contatosEmergencia ?? collect();
    $dependentes = $servidor->dependentes ?? collect();
@endphp
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 6px; }
        h2 { font-size: 13px; margin: 18px 0 8px; }
        .muted { color: #6b7280; }
        .row { margin: 0 0 6px; }
        .box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        th { text-align: left; background: #f3f4f6; }
        .grid2 { width: 100%; }
        .grid2 td { width: 50%; border-bottom: none; padding: 2px 8px; }
        .label { color: #6b7280; width: 160px; display: inline-block; }
    </style>
</head>
<body>
    <h1>Recadastramento - Recad</h1>
    <div class="muted">Data/hora da impressao: {{ $printAt->format('d/m/Y H:i') }}</div>
    <div class="muted">
        Data/hora da conclusao:
        @if($servidor->recadastramento_concluido_em)
            {{ $servidor->recadastramento_concluido_em->format('d/m/Y H:i') }}
        @else
            (nao concluido)
        @endif
    </div>

    <h2>Identificacao</h2>
    <div class="box">
        <div class="row"><span class="label">Matricula:</span> {{ $servidor->matricula }}</div>
        <div class="row"><span class="label">Nome:</span> {{ $servidor->nome }}</div>
        <div class="row"><span class="label">CPF:</span> {{ $doc->cpf ?? '' }}</div>
        <div class="row"><span class="label">E-mail:</span> {{ $servidor->email }}</div>
        <div class="row"><span class="label">Celular:</span> {{ $servidor->celular }}</div>
    </div>

    <h2>Dados pessoais</h2>
    <div class="box">
        <table class="grid2">
            <tr>
                <td><span class="label">Pai:</span> {{ $servidor->pai }}</td>
                <td><span class="label">Mae:</span> {{ $servidor->mae }}</td>
            </tr>
            <tr>
                <td><span class="label">Nascimento:</span> {{ optional($servidor->data_nascimento)->format('d/m/Y') }}</td>
                <td><span class="label">Estado civil:</span> {{ $servidor->estado_civil }}</td>
            </tr>
            <tr>
                <td><span class="label">Conjuge:</span> {{ $servidor->conjuge_nome }}</td>
                <td><span class="label">Naturalidade:</span> {{ $servidor->naturalidade }} / {{ $servidor->naturalidade_uf }}</td>
            </tr>
            <tr>
                <td><span class="label">Nacionalidade:</span> {{ $servidor->nacionalidade }}</td>
                <td><span class="label">Escolaridade:</span> {{ $servidor->escolaridade }}</td>
            </tr>
            <tr>
                <td><span class="label">Curso:</span> {{ $servidor->curso }}</td>
                <td><span class="label">Pos-graduacao:</span> {{ $servidor->pos_graduacao }}</td>
            </tr>
            <tr>
                <td><span class="label">Sexo:</span> {{ $servidor->sexo }}</td>
                <td><span class="label">Raca/cor:</span> {{ $servidor->raca_cor }}</td>
            </tr>
            <tr>
                <td><span class="label">Tipo sanguineo:</span> {{ $servidor->tipo_sanguineo }}</td>
                <td><span class="label">RH:</span> {{ $servidor->fator_rh }}</td>
            </tr>
        </table>
    </div>

    <h2>Endereco e contato</h2>
    <div class="box">
        <table class="grid2">
            <tr>
                <td><span class="label">Endereco:</span> {{ $servidor->endereco }}, {{ $servidor->numero }}</td>
                <td><span class="label">Complemento:</span> {{ $servidor->complemento }}</td>
            </tr>
            <tr>
                <td><span class="label">Bairro:</span> {{ $servidor->bairro }}</td>
                <td><span class="label">CEP:</span> {{ $servidor->cep }}</td>
            </tr>
            <tr>
                <td><span class="label">Cidade/UF:</span> {{ $servidor->cidade }} / {{ $servidor->cidade_uf }}</td>
                <td><span class="label">Fone fixo:</span> {{ $servidor->fone_fixo }}</td>
            </tr>
            <tr>
                <td><span class="label">Celular:</span> {{ $servidor->celular }}</td>
                <td><span class="label">Plano de saude:</span> {{ $servidor->plano_saude }}</td>
            </tr>
        </table>
    </div>

    <h2>Documentacao</h2>
    <div class="box">
        <table class="grid2">
            <tr>
                <td><span class="label">RG:</span> {{ $doc->rg_num ?? '' }}</td>
                <td><span class="label">UF (RG):</span> {{ $doc->rg_uf ?? '' }}</td>
            </tr>
            <tr>
                <td><span class="label">Expedicao RG:</span> {{ optional($doc?->rg_expedicao)->format('d/m/Y') }}</td>
                <td><span class="label">PIS/PASEP:</span> {{ $doc->pis_pasep ?? '' }}</td>
            </tr>
            <tr>
                <td><span class="label">CNH:</span> {{ $doc->cnh_num ?? '' }}</td>
                <td><span class="label">Validade CNH:</span> {{ optional($doc?->cnh_validade)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><span class="label">CTPS:</span> {{ $doc->ctps_num ?? '' }} / {{ $doc->ctps_serie ?? '' }}</td>
                <td><span class="label">Titulo eleitor:</span> {{ $doc->titulo_eleitor_num ?? '' }}</td>
            </tr>
            <tr>
                <td><span class="label">Zona/Secao:</span> {{ $doc->titulo_zona ?? '' }} / {{ $doc->titulo_secao ?? '' }}</td>
                <td><span class="label">Reservista:</span> {{ $doc->reservista_num ?? '' }}</td>
            </tr>
        </table>
    </div>

    <h2>Certidao</h2>
    <div class="box">
        <table class="grid2">
            <tr>
                <td><span class="label">Tipo:</span> {{ $cert->tipo ?? '' }}</td>
                <td><span class="label">Registro:</span> {{ $cert->registro_num ?? '' }}</td>
            </tr>
            <tr>
                <td><span class="label">Livro:</span> {{ $cert->livro ?? '' }}</td>
                <td><span class="label">Folha:</span> {{ $cert->folha ?? '' }}</td>
            </tr>
            <tr>
                <td><span class="label">Matricula:</span> {{ $cert->matricula ?? '' }}</td>
                <td></td>
            </tr>
        </table>
    </div>

    <h2>Ingresso</h2>
    <div class="box">
        <table class="grid2">
            <tr>
                <td><span class="label">Forma:</span> {{ $vinc->forma_ingresso ?? '' }}</td>
                <td><span class="label">Data ingresso:</span> {{ optional($vinc?->data_ingresso)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><span class="label">Nomeacao/Cessao:</span> {{ optional($vinc?->nomeacao_cessao_data)->format('d/m/Y') }}</td>
                <td><span class="label">Portaria:</span> {{ $vinc->portaria_num ?? '' }}</td>
            </tr>
            <tr>
                <td><span class="label">DOE:</span> {{ $vinc->doe_num ?? '' }}</td>
                <td><span class="label">Publicacao DOE:</span> {{ optional($vinc?->doe_publicacao_data)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><span class="label">Cargo/Funcao:</span> {{ $vinc->cargo_funcao ?? '' }}</td>
                <td><span class="label">Orgao origem:</span> {{ $vinc->orgao_origem ?? '' }}</td>
            </tr>
        </table>
    </div>

    <h2>Banco</h2>
    <div class="box">
        <table class="grid2">
            <tr>
                <td><span class="label">Banco:</span> {{ $conta->banco_num ?? '' }}</td>
                <td><span class="label">Agencia:</span> {{ $conta->agencia_num ?? '' }}</td>
            </tr>
            <tr>
                <td><span class="label">Conta:</span> {{ $conta->conta_corrente_num ?? '' }}</td>
                <td></td>
            </tr>
        </table>
    </div>

    <h2>Emergencia</h2>
    <div class="box">
        @if($contatos->count())
            <table>
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Celular</th>
                    <th>Parentesco</th>
                </tr>
                </thead>
                <tbody>
                @foreach($contatos as $c)
                    <tr>
                        <td>{{ $c->nome }}</td>
                        <td>{{ $c->celular }}</td>
                        <td>{{ $c->parentesco }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="muted">(sem registros)</div>
        @endif
    </div>

    <h2>Dependentes</h2>
    <div class="box">
        @if($dependentes->count())
            <table>
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Parentesco</th>
                    <th>Nascimento</th>
                    <th>CPF</th>
                    <th>Tipo</th>
                </tr>
                </thead>
                <tbody>
                @foreach($dependentes as $d)
                    <tr>
                        <td>{{ $d->nome }}</td>
                        <td>{{ $d->parentesco }}</td>
                        <td>{{ optional($d->nascimento)->format('d/m/Y') }}</td>
                        <td>{{ $d->cpf }}</td>
                        <td>{{ $d->tipo_dependente }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="muted">(sem registros)</div>
        @endif
    </div>
</body>
</html>

