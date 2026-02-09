@extends('layouts.app')

@section('title', 'Meu cadastro')

@section('content')
    @php
        $servidor = $servidor ?? new \App\Models\Servidor();
        $doc = $servidor?->documentoPessoal;
        $cert = $servidor?->certidaoServidor;
        $vinculo = $servidor?->vinculo;
        $conta = $servidor?->contaBancaria;
        $contatos = $servidor?->contatosEmergencia ?? collect();
        $dependentes = $servidor?->dependentes ?? collect();
        $disabled = $edit ? '' : 'disabled';
        $tabs = $tabs ?? \App\Services\ServidorConfirmacaoService::labels();
        $confirmacoes = $confirmacoes ?? [];
        $allConfirmed = $allConfirmed ?? false;
        $confirmedCount = is_array($confirmacoes) ? count($confirmacoes) : 0;
    @endphp

    @if($notFound)
        <div class="card" style="border-color:#fca5a5; background:#fef2f2;">
            {{ $notFound }}
        </div>
    @else
        <input type="hidden" id="active-tab" value="{{ old('_active_tab', 'pessoais') }}">

        @if($edit)
            <form method="post" action="{{ route('servidores.self.update') }}" id="perfil-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="_active_tab" class="active-tab-field" value="{{ old('_active_tab', 'pessoais') }}">
        @endif

        <div class="card" style="margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h2 class="section-title" style="margin:0;">Meu cadastro</h2>
                <div class="muted">Matrícula: {{ $matricula }}</div>
                @if($createMode)
                    <div class="muted">Cadastro não encontrado. Preencha os dados e salve.</div>
                @else
                    <div class="muted">
                        Status:
                        @if($servidor->recadastramento_concluido_em)
                            Concluído em {{ $servidor->recadastramento_concluido_em->format('d/m/Y H:i') }}.
                        @else
                            Pendente ({{ $confirmedCount }}/{{ count(\App\Services\ServidorConfirmacaoService::TABS) }} abas confirmadas).
                        @endif
                    </div>
                @endif
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                @if(!$createMode)
                    <a class="btn secondary" href="{{ route('servidores.self.pdf') }}">Imprimir PDF</a>
                @endif

                @if(!$edit)
                    <a class="btn" href="{{ route('servidores.self', ['edit' => 1]) }}">Editar</a>
                @else
                    <button class="btn" type="submit">{{ $createMode ? 'Salvar cadastro' : 'Salvar alterações' }}</button>
                    <a class="btn secondary" href="{{ route('servidores.self') }}">Cancelar</a>
                @endif

                @if(!$edit && !$createMode && !$servidor->recadastramento_concluido_em)
                    <form method="post" action="{{ route('servidores.self.concluir') }}">
                        @csrf
                        <input type="hidden" name="_active_tab" class="active-tab-field" value="pessoais">
                        <button class="btn" type="submit">Concluir</button>
                    </form>
                @endif
            </div>
        </div>

            <div class="card" style="margin-bottom:16px;">
                <div class="tabs" style="display:flex; gap:8px; flex-wrap:wrap;">
                    @foreach(\App\Services\ServidorConfirmacaoService::TABS as $aba)
                        @php($ok = isset($confirmacoes[$aba]))
                        <button type="button" class="btn secondary tab-btn" data-tab="{{ $aba }}">
                            {{ $tabs[$aba] ?? $aba }}
                            @if(!$createMode)
                                <span class="badge" style="margin-left:8px; background:{{ $ok ? '#dcfce7' : '#fee2e2' }}; color:{{ $ok ? '#166534' : '#991b1b' }};">
                                    {{ $ok ? 'OK' : 'Pendente' }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="card tab-panel" data-tab-panel="pessoais">
                <h2 class="section-title">Dados pessoais</h2>
                <div class="grid grid-2">
                    <div class="field">
                        <label>Matrícula</label>
                        <input value="{{ $matricula }}" readonly>
                    </div>
                    <div class="field">
                        <label>Nome</label>
                        <input name="nome" value="{{ old('nome', $servidor->nome) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Pai</label>
                        <input name="pai" value="{{ old('pai', $servidor->pai) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Mãe</label>
                        <input name="mae" value="{{ old('mae', $servidor->mae) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Data de nascimento</label>
                        <input type="date" name="data_nascimento" value="{{ old('data_nascimento', optional($servidor->data_nascimento)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Estado civil</label>
                        <select name="estado_civil" {{ $disabled }}>
                            <option value="">Selecione</option>
                            @foreach(config('recad.estado_civil') as $op)
                                <option value="{{ $op }}" {{ old('estado_civil', $servidor->estado_civil) === $op ? 'selected' : '' }}>{{ $op }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Cônjuge/companheiro(a)</label>
                        <input name="conjuge_nome" value="{{ old('conjuge_nome', $servidor->conjuge_nome) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Naturalidade</label>
                        <input name="naturalidade" value="{{ old('naturalidade', $servidor->naturalidade) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>UF</label>
                        <input name="naturalidade_uf" value="{{ old('naturalidade_uf', $servidor->naturalidade_uf) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Nacionalidade</label>
                        <input name="nacionalidade" value="{{ old('nacionalidade', $servidor->nacionalidade) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Escolaridade</label>
                        <div style="display:flex; gap:16px; flex-wrap:wrap;">
                            @php($esc = old('escolaridade', $servidor->escolaridade))
                            @foreach(config('recad.escolaridade') as $op)
                                <label style="display:flex; gap:6px; align-items:center;">
                                    <input type="radio" name="escolaridade" value="{{ $op }}" {{ $esc === $op ? 'checked' : '' }} {{ $disabled }}>
                                    {{ $op }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field">
                        <label>Curso</label>
                        <input name="curso" value="{{ old('curso', $servidor->curso) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Pós-graduação</label>
                        <input name="pos_graduacao" value="{{ old('pos_graduacao', $servidor->pos_graduacao) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Curso (pós)</label>
                        <input name="pos_curso" value="{{ old('pos_curso', $servidor->pos_curso) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Início (pós)</label>
                        <input type="date" name="pos_inicio" value="{{ old('pos_inicio', optional($servidor->pos_inicio)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Fim (pós)</label>
                        <input type="date" name="pos_fim" value="{{ old('pos_fim', optional($servidor->pos_fim)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Carga horária (pós)</label>
                        <input type="number" name="pos_carga_horaria" value="{{ old('pos_carga_horaria', $servidor->pos_carga_horaria) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Sexo</label>
                        <div style="display:flex; gap:16px; flex-wrap:wrap;">
                            @php($sexo = old('sexo', $servidor->sexo))
                            @foreach(config('recad.sexo') as $op)
                                <label style="display:flex; gap:6px; align-items:center;">
                                    <input type="radio" name="sexo" value="{{ $op }}" {{ $sexo === $op ? 'checked' : '' }} {{ $disabled }}>
                                    {{ $op }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field">
                        <label>Tipo sanguíneo</label>
                        <select name="tipo_sanguineo" {{ $disabled }}>
                            <option value="">Selecione</option>
                            @foreach(config('recad.tipo_sanguineo') as $op)
                                <option value="{{ $op }}" {{ old('tipo_sanguineo', $servidor->tipo_sanguineo) === $op ? 'selected' : '' }}>{{ $op }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>RH</label>
                        <select name="fator_rh" {{ $disabled }}>
                            <option value="">Selecione</option>
                            @foreach(config('recad.fator_rh') as $op)
                                <option value="{{ $op }}" {{ old('fator_rh', $servidor->fator_rh) === $op ? 'selected' : '' }}>{{ $op }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Raça/Cor</label>
                        <div style="display:flex; gap:16px; flex-wrap:wrap;">
                            @php($rc = old('raca_cor', $servidor->raca_cor))
                            @foreach(config('recad.raca_cor') as $op)
                                <label style="display:flex; gap:6px; align-items:center;">
                                    <input type="radio" name="raca_cor" value="{{ $op }}" {{ $rc === $op ? 'checked' : '' }} {{ $disabled }}>
                                    {{ $op }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @if(!$edit && !$createMode)
                    @include('servidores.partials.confirmacao', ['aba' => 'pessoais', 'confirmacoes' => $confirmacoes])
                @endif
            </div>

            <div class="card tab-panel" data-tab-panel="endereco" style="display:none;">
                <h2 class="section-title">Endereço e contato</h2>
                <div class="grid grid-2">
                    <div class="field">
                        <label>Endereço</label>
                        <input name="endereco" value="{{ old('endereco', $servidor->endereco) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Número</label>
                        <input name="numero" value="{{ old('numero', $servidor->numero) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Bairro</label>
                        <input name="bairro" value="{{ old('bairro', $servidor->bairro) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Complemento</label>
                        <input name="complemento" value="{{ old('complemento', $servidor->complemento) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>CEP</label>
                        <input name="cep" value="{{ old('cep', $servidor->cep) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Cidade</label>
                        <input name="cidade" value="{{ old('cidade', $servidor->cidade) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>UF</label>
                        <input name="cidade_uf" value="{{ old('cidade_uf', $servidor->cidade_uf) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Fone fixo</label>
                        <input name="fone_fixo" value="{{ old('fone_fixo', $servidor->fone_fixo) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Celular</label>
                        <input name="celular" value="{{ old('celular', $servidor->celular) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>E-mail</label>
                        <input type="email" name="email" value="{{ old('email', $servidor->email) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Plano de saúde</label>
                        <input name="plano_saude" value="{{ old('plano_saude', $servidor->plano_saude) }}" {{ $disabled }}>
                    </div>
                </div>
                @if(!$edit && !$createMode)
                    @include('servidores.partials.confirmacao', ['aba' => 'endereco', 'confirmacoes' => $confirmacoes])
                @endif
            </div>

            <div class="card tab-panel" data-tab-panel="documentos" style="display:none;">
                <h2 class="section-title">Documentação</h2>
                <div class="grid grid-2">
                    <div class="field">
                        <label>RG</label>
                        <input name="rg_num" value="{{ old('rg_num', $doc->rg_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>UF (RG)</label>
                        <input name="rg_uf" value="{{ old('rg_uf', $doc->rg_uf ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Expedição (RG)</label>
                        <input type="date" name="rg_expedicao" value="{{ old('rg_expedicao', optional($doc?->rg_expedicao)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>CPF</label>
                        <input name="cpf" value="{{ old('cpf', $doc->cpf ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Identidade Profissional</label>
                        <input name="id_prof_num" value="{{ old('id_prof_num', $doc->id_prof_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Tipo</label>
                        <input name="id_prof_tipo" value="{{ old('id_prof_tipo', $doc->id_prof_tipo ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>UF (ID Prof.)</label>
                        <input name="id_prof_uf" value="{{ old('id_prof_uf', $doc->id_prof_uf ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>CNH</label>
                        <input name="cnh_num" value="{{ old('cnh_num', $doc->cnh_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Categoria CNH</label>
                        <input name="cnh_categoria" value="{{ old('cnh_categoria', $doc->cnh_categoria ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Validade CNH</label>
                        <input type="date" name="cnh_validade" value="{{ old('cnh_validade', optional($doc?->cnh_validade)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>UF (CNH)</label>
                        <input name="cnh_uf" value="{{ old('cnh_uf', $doc->cnh_uf ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>CTPS</label>
                        <input name="ctps_num" value="{{ old('ctps_num', $doc->ctps_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Série CTPS</label>
                        <input name="ctps_serie" value="{{ old('ctps_serie', $doc->ctps_serie ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Expedição CTPS</label>
                        <input type="date" name="ctps_expedicao" value="{{ old('ctps_expedicao', optional($doc?->ctps_expedicao)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Título de eleitor</label>
                        <input name="titulo_eleitor_num" value="{{ old('titulo_eleitor_num', $doc->titulo_eleitor_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Zona</label>
                        <input name="titulo_zona" value="{{ old('titulo_zona', $doc->titulo_zona ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Seção</label>
                        <input name="titulo_secao" value="{{ old('titulo_secao', $doc->titulo_secao ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Reservista</label>
                        <input name="reservista_num" value="{{ old('reservista_num', $doc->reservista_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Categoria reservista</label>
                        <input name="reservista_categoria" value="{{ old('reservista_categoria', $doc->reservista_categoria ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>UF (reservista)</label>
                        <input name="reservista_uf" value="{{ old('reservista_uf', $doc->reservista_uf ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>PIS/PASEP</label>
                        <input name="pis_pasep" value="{{ old('pis_pasep', $doc->pis_pasep ?? '') }}" {{ $disabled }}>
                    </div>
                </div>
                @if(!$edit && !$createMode)
                    @include('servidores.partials.confirmacao', ['aba' => 'documentos', 'confirmacoes' => $confirmacoes])
                @endif
            </div>

            <div class="card tab-panel" data-tab-panel="certidao" style="display:none;">
                <h2 class="section-title">Certidão</h2>
                <div class="grid grid-2">
                    <div class="field">
                        <label>Tipo</label>
                        <div style="display:flex; gap:16px; flex-wrap:wrap;">
                            @php($ct = old('certidao_tipo', $cert->tipo ?? ''))
                            @foreach(config('recad.certidao_tipo') as $op)
                                <label style="display:flex; gap:6px; align-items:center;">
                                    <input type="radio" name="certidao_tipo" value="{{ $op }}" {{ $ct === $op ? 'checked' : '' }} {{ $disabled }}>
                                    {{ $op }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field">
                        <label>Registro</label>
                        <input name="certidao_registro_num" value="{{ old('certidao_registro_num', $cert->registro_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Livro</label>
                        <input name="certidao_livro" value="{{ old('certidao_livro', $cert->livro ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Folha</label>
                        <input name="certidao_folha" value="{{ old('certidao_folha', $cert->folha ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Matrícula</label>
                        <input name="certidao_matricula" value="{{ old('certidao_matricula', $cert->matricula ?? '') }}" {{ $disabled }}>
                    </div>
                </div>
                @if(!$edit && !$createMode)
                    @include('servidores.partials.confirmacao', ['aba' => 'certidao', 'confirmacoes' => $confirmacoes])
                @endif
            </div>

            <div class="card tab-panel" data-tab-panel="ingresso" style="display:none;">
                <h2 class="section-title">Forma de ingresso</h2>
                <div class="grid grid-2">
                    <div class="field">
                        <label>Forma</label>
                        <div style="display:flex; gap:16px; flex-wrap:wrap;">
                            @php($fi = old('forma_ingresso', $vinculo->forma_ingresso ?? ''))
                            @foreach(config('recad.forma_ingresso') as $op)
                                <label style="display:flex; gap:6px; align-items:center;">
                                    <input type="radio" name="forma_ingresso" value="{{ $op }}" {{ $fi === $op ? 'checked' : '' }} {{ $disabled }}>
                                    {{ $op }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field">
                        <label>Data de ingresso</label>
                        <input type="date" name="data_ingresso" value="{{ old('data_ingresso', optional($vinculo?->data_ingresso)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Nomeação/Cessão</label>
                        <input type="date" name="nomeacao_cessao_data" value="{{ old('nomeacao_cessao_data', optional($vinculo?->nomeacao_cessao_data)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Portaria nº</label>
                        <input name="portaria_num" value="{{ old('portaria_num', $vinculo->portaria_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>DOE nº</label>
                        <input name="doe_num" value="{{ old('doe_num', $vinculo->doe_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Publicação DOE</label>
                        <input type="date" name="doe_publicacao_data" value="{{ old('doe_publicacao_data', optional($vinculo?->doe_publicacao_data)->format('Y-m-d')) }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Cargo/Função</label>
                        <input name="cargo_funcao" value="{{ old('cargo_funcao', $vinculo->cargo_funcao ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Órgão de origem</label>
                        <input name="orgao_origem" value="{{ old('orgao_origem', $vinculo->orgao_origem ?? '') }}" {{ $disabled }}>
                    </div>
                </div>
                @if(!$edit && !$createMode)
                    @include('servidores.partials.confirmacao', ['aba' => 'ingresso', 'confirmacoes' => $confirmacoes])
                @endif
            </div>

            <div class="card tab-panel" data-tab-panel="banco" style="display:none;">
                <h2 class="section-title">Dados bancários</h2>
                <div class="grid grid-2">
                    <div class="field">
                        <label>Banco nº</label>
                        <input name="banco_num" value="{{ old('banco_num', $conta->banco_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Agência nº</label>
                        <input name="agencia_num" value="{{ old('agencia_num', $conta->agencia_num ?? '') }}" {{ $disabled }}>
                    </div>
                    <div class="field">
                        <label>Conta corrente nº</label>
                        <input name="conta_corrente_num" value="{{ old('conta_corrente_num', $conta->conta_corrente_num ?? '') }}" {{ $disabled }}>
                    </div>
                </div>
                @if(!$edit && !$createMode)
                    @include('servidores.partials.confirmacao', ['aba' => 'banco', 'confirmacoes' => $confirmacoes])
                @endif
            </div>

            <div class="card tab-panel" data-tab-panel="emergencia" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <h2 class="section-title" style="margin:0;">Contatos de emergência</h2>
                    @if($edit)
                        <button type="button" class="btn secondary" id="add-contato">Adicionar contato</button>
                    @endif
                </div>
                <div id="contatos-container" style="margin-top:16px;">
                    @foreach($contatos as $index => $contato)
                        <div class="card" style="margin-top:12px; border-style:dashed;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                                <strong>Contato</strong>
                                @if($edit)
                                    <button type="button" class="btn secondary remove-contato">Remover</button>
                                @endif
                            </div>
                            <div class="grid grid-2" style="margin-top:12px;">
                                <div class="field">
                                    <label>Nome</label>
                                    <input name="contatos_emergencia[{{ $index }}][nome]" value="{{ $contato->nome }}" {{ $disabled }}>
                                </div>
                                <div class="field">
                                    <label>Celular</label>
                                    <input name="contatos_emergencia[{{ $index }}][celular]" value="{{ $contato->celular }}" {{ $disabled }}>
                                </div>
                                <div class="field">
                                    <label>Parentesco</label>
                                    <input name="contatos_emergencia[{{ $index }}][parentesco]" value="{{ $contato->parentesco }}" {{ $disabled }}>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if(!$edit && !$createMode)
                    @include('servidores.partials.confirmacao', ['aba' => 'emergencia', 'confirmacoes' => $confirmacoes])
                @endif
            </div>

            <div class="card tab-panel" data-tab-panel="dependentes" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <h2 class="section-title" style="margin:0;">Dependentes</h2>
                    @if($edit)
                        <button type="button" class="btn secondary" id="add-dependente">Adicionar dependente</button>
                    @endif
                </div>
                <div id="dependentes-container" style="margin-top:16px;">
                    @foreach($dependentes as $index => $dep)
                        <div class="card" style="margin-top:12px; border-style:dashed;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                                <strong>Dependente</strong>
                                @if($edit)
                                    <button type="button" class="btn secondary remove-dependente">Remover</button>
                                @endif
                            </div>
                            <div class="grid grid-2" style="margin-top:12px;">
                                <div class="field">
                                    <label>Nome</label>
                                    <input name="dependentes[{{ $index }}][nome]" value="{{ $dep->nome }}" {{ $disabled }}>
                                </div>
                                <div class="field">
                                    <label>Parentesco</label>
                                    <input name="dependentes[{{ $index }}][parentesco]" value="{{ $dep->parentesco }}" {{ $disabled }}>
                                </div>
                                <div class="field">
                                    <label>Nascimento</label>
                                    <input type="date" name="dependentes[{{ $index }}][nascimento]" value="{{ optional($dep->nascimento)->format('Y-m-d') }}" {{ $disabled }}>
                                </div>
                                <div class="field">
                                    <label>RG</label>
                                    <input name="dependentes[{{ $index }}][rg_num]" value="{{ $dep->rg_num }}" {{ $disabled }}>
                                </div>
                                <div class="field">
                                    <label>Expedição RG</label>
                                    <input type="date" name="dependentes[{{ $index }}][rg_expedicao]" value="{{ optional($dep->rg_expedicao)->format('Y-m-d') }}" {{ $disabled }}>
                                </div>
                                <div class="field">
                                    <label>CPF</label>
                                    <input name="dependentes[{{ $index }}][cpf]" value="{{ $dep->cpf }}" {{ $disabled }}>
                                </div>
                                <div class="field">
                                    <label>Certidão</label>
                                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                        @foreach(config('recad.certidao_tipo') as $op)
                                            <label style="display:flex; gap:6px; align-items:center;">
                                                <input type="radio" name="dependentes[{{ $index }}][certidao_tipo]" value="{{ $op }}" {{ $dep->certidao_tipo === $op ? 'checked' : '' }} {{ $disabled }}>
                                                {{ $op }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="field">
                                    <label>Sexo</label>
                                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                        @foreach(config('recad.sexo') as $op)
                                            <label style="display:flex; gap:6px; align-items:center;">
                                                <input type="radio" name="dependentes[{{ $index }}][sexo]" value="{{ $op }}" {{ $dep->sexo === $op ? 'checked' : '' }} {{ $disabled }}>
                                                {{ $op }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="field">
                                    <label>Tipo dependente</label>
                                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                        @foreach(config('recad.dependente_tipo') as $op)
                                            <label style="display:flex; gap:6px; align-items:center;">
                                                <input type="radio" name="dependentes[{{ $index }}][tipo_dependente]" value="{{ $op }}" {{ $dep->tipo_dependente === $op ? 'checked' : '' }} {{ $disabled }}>
                                                {{ $op }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if(!$edit && !$createMode)
                    @include('servidores.partials.confirmacao', ['aba' => 'dependentes', 'confirmacoes' => $confirmacoes])
                @endif
            </div>
        @if($edit)
            </form>
        @endif

        @if($edit)
            <template id="contato-template">
                <div class="card" style="margin-top:12px; border-style:dashed;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <strong>Contato</strong>
                        <button type="button" class="btn secondary remove-contato">Remover</button>
                    </div>
                    <div class="grid grid-2" style="margin-top:12px;">
                        <div class="field">
                            <label>Nome</label>
                            <input data-name="nome">
                        </div>
                        <div class="field">
                            <label>Celular</label>
                            <input data-name="celular">
                        </div>
                        <div class="field">
                            <label>Parentesco</label>
                            <input data-name="parentesco">
                        </div>
                    </div>
                </div>
            </template>

            <template id="dependente-template">
                <div class="card" style="margin-top:12px; border-style:dashed;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <strong>Dependente</strong>
                        <button type="button" class="btn secondary remove-dependente">Remover</button>
                    </div>
                    <div class="grid grid-2" style="margin-top:12px;">
                        <div class="field">
                            <label>Nome</label>
                            <input data-name="nome">
                        </div>
                        <div class="field">
                            <label>Parentesco</label>
                            <input data-name="parentesco">
                        </div>
                        <div class="field">
                            <label>Nascimento</label>
                            <input type="date" data-name="nascimento">
                        </div>
                        <div class="field">
                            <label>RG</label>
                            <input data-name="rg_num">
                        </div>
                        <div class="field">
                            <label>Expedição RG</label>
                            <input type="date" data-name="rg_expedicao">
                        </div>
                        <div class="field">
                            <label>CPF</label>
                            <input data-name="cpf">
                        </div>
                        <div class="field">
                            <label>Certidão</label>
                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                @foreach(config('recad.certidao_tipo') as $op)
                                    <label style="display:flex; gap:6px; align-items:center;">
                                        <input type="radio" data-name="certidao_tipo" value="{{ $op }}">
                                        {{ $op }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="field">
                            <label>Sexo</label>
                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                @foreach(config('recad.sexo') as $op)
                                    <label style="display:flex; gap:6px; align-items:center;">
                                        <input type="radio" data-name="sexo" value="{{ $op }}">
                                        {{ $op }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="field">
                            <label>Tipo dependente</label>
                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                @foreach(config('recad.dependente_tipo') as $op)
                                    <label style="display:flex; gap:6px; align-items:center;">
                                        <input type="radio" data-name="tipo_dependente" value="{{ $op }}">
                                        {{ $op }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        @endif

        <script>
            (function () {
                const buttons = document.querySelectorAll('.tab-btn');
                const panels = document.querySelectorAll('.tab-panel');
                let dirty = false;
                const edit = {{ $edit ? 'true' : 'false' }};

                function setActive(tab) {
                    panels.forEach(panel => {
                        panel.style.display = panel.dataset.tabPanel === tab ? 'block' : 'none';
                    });
                    // Persist active tab in the URL so navigation (e.g. Edit) keeps context.
                    if (tab) {
                        history.replaceState(null, '', '#' + tab);
                    }
                    const activeTab = document.getElementById('active-tab');
                    if (activeTab) activeTab.value = tab || 'pessoais';
                    document.querySelectorAll('.active-tab-field').forEach(el => {
                        el.value = tab || 'pessoais';
                    });
                }

                buttons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (edit && dirty) {
                            const ok = confirm('Há alterações não salvas. Salve antes de sair.');
                            if (!ok) return;
                        }
                        setActive(btn.dataset.tab);
                    });
                });

                const inputTab = (document.getElementById('active-tab')?.value || '').trim();
                const initialTab = (location.hash || '').replace('#', '') || inputTab || 'pessoais';
                const hasPanel = Array.from(panels).some(p => p.dataset.tabPanel === initialTab);
                setActive(hasPanel ? initialTab : 'pessoais');

                // Keep the current tab when navigating between view/edit (links that don't include the hash).
                document.addEventListener('click', (e) => {
                    const a = e.target.closest('a');
                    if (!a) return;
                    if (!a.href) return;

                    // Only rewrite navigation when we have a tab selected.
                    const hash = location.hash || '';
                    if (!hash) return;

                    let url;
                    try {
                        url = new URL(a.href, window.location.href);
                    } catch {
                        return;
                    }

                    const isSelf = url.pathname.endsWith('/meu-cadastro') || url.pathname === '/';
                    const isEdit = url.searchParams.get('edit') === '1';
                    const isCancel = !isEdit && edit && url.pathname.endsWith('/meu-cadastro');

                    if (!isSelf) return;

                    // View -> Edit: /meu-cadastro?edit=1
                    if (!edit && isEdit) {
                        e.preventDefault();
                        window.location.href = url.toString() + hash;
                        return;
                    }

                    // Edit -> View (cancel): /meu-cadastro
                    if (isCancel) {
                        e.preventDefault();
                        window.location.href = url.toString() + hash;
                    }
                });

                if (edit) {
                    const form = document.getElementById('perfil-form');

                    // Dirty tracking based on meaningful (non-empty) form values.
                    // This avoids false positives when the user adds/removes empty blocks.
                    const IGNORED_KEYS = new Set(['_token', '_method', '_active_tab']);
                    const snapshot = () => {
                        const fd = new FormData(form);
                        const pairs = [];
                        for (const [key, value] of fd.entries()) {
                            if (IGNORED_KEYS.has(key)) continue;
                            if (typeof value !== 'string') continue;
                            const v = value.trim();
                            if (!v) continue;
                            pairs.push([key, v]);
                        }
                        pairs.sort((a, b) => {
                            if (a[0] === b[0]) return a[1] < b[1] ? -1 : (a[1] > b[1] ? 1 : 0);
                            return a[0] < b[0] ? -1 : 1;
                        });
                        return JSON.stringify(pairs);
                    };

                    const initialSnapshot = snapshot();
                    const updateDirty = () => { dirty = snapshot() !== initialSnapshot; };

                    form.addEventListener('input', updateDirty);
                    form.addEventListener('change', updateDirty);

                    const onBeforeUnload = (e) => {
                        if (!dirty) return;
                        e.preventDefault();
                        e.returnValue = '';
                    };
                    window.addEventListener('beforeunload', onBeforeUnload);

                    // When the user intentionally saves, we should not warn about unsaved changes.
                    form.addEventListener('submit', () => {
                        dirty = false;
                        window.removeEventListener('beforeunload', onBeforeUnload);
                    });

                    const contatosContainer = document.getElementById('contatos-container');
                    const dependentesContainer = document.getElementById('dependentes-container');
                    const contatoTemplate = document.getElementById('contato-template');
                    const dependenteTemplate = document.getElementById('dependente-template');

                    let contatoIndex = {{ $contatos->count() }};
                    let dependenteIndex = {{ $dependentes->count() }};

                    const addContato = () => {
                        const clone = contatoTemplate.content.cloneNode(true);
                        const wrapper = clone.querySelector('.card');
                        wrapper.querySelectorAll('[data-name]').forEach((el) => {
                            const key = el.getAttribute('data-name');
                            el.setAttribute('name', `contatos_emergencia[${contatoIndex}][${key}]`);
                        });
                        wrapper.querySelector('.remove-contato').addEventListener('click', () => {
                            wrapper.remove();
                            updateDirty();
                        });
                        contatosContainer.appendChild(wrapper);
                        contatoIndex += 1;
                        updateDirty();
                    };

                    const addDependente = () => {
                        const clone = dependenteTemplate.content.cloneNode(true);
                        const wrapper = clone.querySelector('.card');
                        wrapper.querySelectorAll('[data-name]').forEach((el) => {
                            const key = el.getAttribute('data-name');
                            el.setAttribute('name', `dependentes[${dependenteIndex}][${key}]`);
                        });
                        wrapper.querySelector('.remove-dependente').addEventListener('click', () => {
                            wrapper.remove();
                            updateDirty();
                        });
                        dependentesContainer.appendChild(wrapper);
                        dependenteIndex += 1;
                        updateDirty();
                    };

                    document.getElementById('add-contato')?.addEventListener('click', addContato);
                    document.getElementById('add-dependente')?.addEventListener('click', addDependente);

                    document.querySelectorAll('.remove-contato').forEach(btn => {
                        btn.addEventListener('click', () => {
                            btn.closest('.card')?.remove();
                            updateDirty();
                        });
                    });
                    document.querySelectorAll('.remove-dependente').forEach(btn => {
                        btn.addEventListener('click', () => {
                            btn.closest('.card')?.remove();
                            updateDirty();
                        });
                    });

                    // Ensure initial state is clean.
                    updateDirty();
                }
            })();
        </script>
    @endif
@endsection
