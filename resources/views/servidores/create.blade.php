@extends('layouts.app')

@section('title', 'Novo servidor')

@section('content')
    <form method="post" action="{{ route('servidores.store') }}" class="grid" style="gap:20px;">
        @csrf

        <div class="card">
            <h2 class="section-title">Dados pessoais</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label>Matrícula</label>
                    <input name="matricula" value="{{ old('matricula') }}" required>
                </div>
                <div class="field">
                    <label>Nome</label>
                    <input name="nome" value="{{ old('nome') }}" required>
                </div>
                <div class="field">
                    <label>Pai</label>
                    <input name="pai" value="{{ old('pai') }}">
                </div>
                <div class="field">
                    <label>Mãe</label>
                    <input name="mae" value="{{ old('mae') }}">
                </div>
                <div class="field">
                    <label>Data de nascimento</label>
                    <input type="date" name="data_nascimento" value="{{ old('data_nascimento') }}">
                </div>
                <div class="field">
                    <label>Estado civil</label>
                    <select name="estado_civil">
                        <option value="">Selecione</option>
                        @foreach(config('recad.estado_civil') as $op)
                            <option value="{{ $op }}" {{ old('estado_civil') === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Cônjuge/companheiro(a)</label>
                    <input name="conjuge_nome" value="{{ old('conjuge_nome') }}">
                </div>
                <div class="field">
                    <label>Naturalidade</label>
                    <input name="naturalidade" value="{{ old('naturalidade') }}">
                </div>
                <div class="field">
                    <label>UF</label>
                    <input name="naturalidade_uf" value="{{ old('naturalidade_uf') }}">
                </div>
                <div class="field">
                    <label>Nacionalidade</label>
                    <input name="nacionalidade" value="{{ old('nacionalidade') }}">
                </div>
                <div class="field">
                    <label>Escolaridade</label>
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                        @php($esc = old('escolaridade'))
                        @foreach(config('recad.escolaridade') as $op)
                            <label style="display:flex; gap:6px; align-items:center;">
                                <input type="radio" name="escolaridade" value="{{ $op }}" {{ $esc === $op ? 'checked' : '' }}>
                                {{ $op }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Curso</label>
                    <input name="curso" value="{{ old('curso') }}">
                </div>
                <div class="field">
                    <label>Pós-graduação</label>
                    <input name="pos_graduacao" value="{{ old('pos_graduacao') }}">
                </div>
                <div class="field">
                    <label>Curso (pós)</label>
                    <input name="pos_curso" value="{{ old('pos_curso') }}">
                </div>
                <div class="field">
                    <label>Início (pós)</label>
                    <input type="date" name="pos_inicio" value="{{ old('pos_inicio') }}">
                </div>
                <div class="field">
                    <label>Fim (pós)</label>
                    <input type="date" name="pos_fim" value="{{ old('pos_fim') }}">
                </div>
                <div class="field">
                    <label>Carga horária (pós)</label>
                    <input type="number" name="pos_carga_horaria" value="{{ old('pos_carga_horaria') }}">
                </div>
                <div class="field">
                    <label>Sexo</label>
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                        @php($sexo = old('sexo'))
                        @foreach(config('recad.sexo') as $op)
                            <label style="display:flex; gap:6px; align-items:center;">
                                <input type="radio" name="sexo" value="{{ $op }}" {{ $sexo === $op ? 'checked' : '' }}>
                                {{ $op }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Tipo sanguíneo</label>
                    <select name="tipo_sanguineo">
                        <option value="">Selecione</option>
                        @foreach(config('recad.tipo_sanguineo') as $op)
                            <option value="{{ $op }}" {{ old('tipo_sanguineo') === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>RH</label>
                    <select name="fator_rh">
                        <option value="">Selecione</option>
                        @foreach(config('recad.fator_rh') as $op)
                            <option value="{{ $op }}" {{ old('fator_rh') === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Raça/Cor</label>
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                        @php($rc = old('raca_cor'))
                        @foreach(config('recad.raca_cor') as $op)
                            <label style="display:flex; gap:6px; align-items:center;">
                                <input type="radio" name="raca_cor" value="{{ $op }}" {{ $rc === $op ? 'checked' : '' }}>
                                {{ $op }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Endereço e contato</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label>Endereço</label>
                    <input name="endereco" value="{{ old('endereco') }}">
                </div>
                <div class="field">
                    <label>Número</label>
                    <input name="numero" value="{{ old('numero') }}">
                </div>
                <div class="field">
                    <label>Bairro</label>
                    <input name="bairro" value="{{ old('bairro') }}">
                </div>
                <div class="field">
                    <label>Complemento</label>
                    <input name="complemento" value="{{ old('complemento') }}">
                </div>
                <div class="field">
                    <label>CEP</label>
                    <input name="cep" value="{{ old('cep') }}">
                </div>
                <div class="field">
                    <label>Cidade</label>
                    <input name="cidade" value="{{ old('cidade') }}">
                </div>
                <div class="field">
                    <label>UF</label>
                    <input name="cidade_uf" value="{{ old('cidade_uf') }}">
                </div>
                <div class="field">
                    <label>Fone fixo</label>
                    <input name="fone_fixo" value="{{ old('fone_fixo') }}">
                </div>
                <div class="field">
                    <label>Celular</label>
                    <input name="celular" value="{{ old('celular') }}">
                </div>
                <div class="field">
                    <label>E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="field">
                    <label>Plano de saúde</label>
                    <input name="plano_saude" value="{{ old('plano_saude') }}">
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Documentação</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label>RG</label>
                    <input name="rg_num" value="{{ old('rg_num') }}">
                </div>
                <div class="field">
                    <label>UF (RG)</label>
                    <input name="rg_uf" value="{{ old('rg_uf') }}">
                </div>
                <div class="field">
                    <label>Expedição (RG)</label>
                    <input type="date" name="rg_expedicao" value="{{ old('rg_expedicao') }}">
                </div>
                <div class="field">
                    <label>CPF</label>
                    <input name="cpf" value="{{ old('cpf') }}">
                </div>
                <div class="field">
                    <label>Identidade Profissional</label>
                    <input name="id_prof_num" value="{{ old('id_prof_num') }}">
                </div>
                <div class="field">
                    <label>Tipo</label>
                    <input name="id_prof_tipo" value="{{ old('id_prof_tipo') }}">
                </div>
                <div class="field">
                    <label>UF (ID Prof.)</label>
                    <input name="id_prof_uf" value="{{ old('id_prof_uf') }}">
                </div>
                <div class="field">
                    <label>CNH</label>
                    <input name="cnh_num" value="{{ old('cnh_num') }}">
                </div>
                <div class="field">
                    <label>Categoria CNH</label>
                    <input name="cnh_categoria" value="{{ old('cnh_categoria') }}">
                </div>
                <div class="field">
                    <label>Validade CNH</label>
                    <input type="date" name="cnh_validade" value="{{ old('cnh_validade') }}">
                </div>
                <div class="field">
                    <label>UF (CNH)</label>
                    <input name="cnh_uf" value="{{ old('cnh_uf') }}">
                </div>
                <div class="field">
                    <label>CTPS</label>
                    <input name="ctps_num" value="{{ old('ctps_num') }}">
                </div>
                <div class="field">
                    <label>Série CTPS</label>
                    <input name="ctps_serie" value="{{ old('ctps_serie') }}">
                </div>
                <div class="field">
                    <label>Expedição CTPS</label>
                    <input type="date" name="ctps_expedicao" value="{{ old('ctps_expedicao') }}">
                </div>
                <div class="field">
                    <label>Título de eleitor</label>
                    <input name="titulo_eleitor_num" value="{{ old('titulo_eleitor_num') }}">
                </div>
                <div class="field">
                    <label>Zona</label>
                    <input name="titulo_zona" value="{{ old('titulo_zona') }}">
                </div>
                <div class="field">
                    <label>Seção</label>
                    <input name="titulo_secao" value="{{ old('titulo_secao') }}">
                </div>
                <div class="field">
                    <label>Reservista</label>
                    <input name="reservista_num" value="{{ old('reservista_num') }}">
                </div>
                <div class="field">
                    <label>Categoria reservista</label>
                    <input name="reservista_categoria" value="{{ old('reservista_categoria') }}">
                </div>
                <div class="field">
                    <label>UF (reservista)</label>
                    <input name="reservista_uf" value="{{ old('reservista_uf') }}">
                </div>
                <div class="field">
                    <label>PIS/PASEP</label>
                    <input name="pis_pasep" value="{{ old('pis_pasep') }}">
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Certidão</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label>Tipo</label>
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                        @php($ct = old('certidao_tipo'))
                        @foreach(config('recad.certidao_tipo') as $op)
                            <label style="display:flex; gap:6px; align-items:center;">
                                <input type="radio" name="certidao_tipo" value="{{ $op }}" {{ $ct === $op ? 'checked' : '' }}>
                                {{ $op }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Registro</label>
                    <input name="certidao_registro_num" value="{{ old('certidao_registro_num') }}">
                </div>
                <div class="field">
                    <label>Livro</label>
                    <input name="certidao_livro" value="{{ old('certidao_livro') }}">
                </div>
                <div class="field">
                    <label>Folha</label>
                    <input name="certidao_folha" value="{{ old('certidao_folha') }}">
                </div>
                <div class="field">
                    <label>Matrícula</label>
                    <input name="certidao_matricula" value="{{ old('certidao_matricula') }}">
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Forma de ingresso</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label>Forma</label>
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                        @php($fi = old('forma_ingresso'))
                        @foreach(config('recad.forma_ingresso') as $op)
                            <label style="display:flex; gap:6px; align-items:center;">
                                <input type="radio" name="forma_ingresso" value="{{ $op }}" {{ $fi === $op ? 'checked' : '' }}>
                                {{ $op }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Data de ingresso</label>
                    <input type="date" name="data_ingresso" value="{{ old('data_ingresso') }}">
                </div>
                <div class="field">
                    <label>Nomeação/Cessão</label>
                    <input type="date" name="nomeacao_cessao_data" value="{{ old('nomeacao_cessao_data') }}">
                </div>
                <div class="field">
                    <label>Portaria nº</label>
                    <input name="portaria_num" value="{{ old('portaria_num') }}">
                </div>
                <div class="field">
                    <label>DOE nº</label>
                    <input name="doe_num" value="{{ old('doe_num') }}">
                </div>
                <div class="field">
                    <label>Publicação DOE</label>
                    <input type="date" name="doe_publicacao_data" value="{{ old('doe_publicacao_data') }}">
                </div>
                <div class="field">
                    <label>Cargo/Função</label>
                    <input name="cargo_funcao" value="{{ old('cargo_funcao') }}">
                </div>
                <div class="field">
                    <label>Órgão de origem</label>
                    <input name="orgao_origem" value="{{ old('orgao_origem') }}">
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Dados bancários</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label>Banco nº</label>
                    <input name="banco_num" value="{{ old('banco_num') }}">
                </div>
                <div class="field">
                    <label>Agência nº</label>
                    <input name="agencia_num" value="{{ old('agencia_num') }}">
                </div>
                <div class="field">
                    <label>Conta corrente nº</label>
                    <input name="conta_corrente_num" value="{{ old('conta_corrente_num') }}">
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Contatos de emergência</h2>
            @for ($i = 0; $i < 2; $i++)
                <div class="grid grid-2" style="margin-bottom:12px;">
                    <div class="field">
                        <label>Nome</label>
                        <input name="contatos_emergencia[{{ $i }}][nome]">
                    </div>
                    <div class="field">
                        <label>Celular</label>
                        <input name="contatos_emergencia[{{ $i }}][celular]">
                    </div>
                    <div class="field">
                        <label>Parentesco</label>
                        <input name="contatos_emergencia[{{ $i }}][parentesco]">
                    </div>
                </div>
            @endfor
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <h2 class="section-title" style="margin:0;">Dependentes</h2>
                <button type="button" class="btn secondary" id="add-dependente">Adicionar dependente</button>
            </div>
            <div id="dependentes-container" style="margin-top:16px;"></div>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="btn">Salvar</button>
            <a class="btn secondary" href="{{ route('servidores.index') }}">Cancelar</a>
        </div>
    </form>

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

    <script>
        (function () {
            const container = document.getElementById('dependentes-container');
            const addBtn = document.getElementById('add-dependente');
            const template = document.getElementById('dependente-template');
            let index = 0;

            function addDependente() {
                const clone = template.content.cloneNode(true);
                const wrapper = clone.querySelector('.card');
                const inputs = wrapper.querySelectorAll('[data-name]');
                inputs.forEach((el) => {
                    const key = el.getAttribute('data-name');
                    el.setAttribute('name', `dependentes[${index}][${key}]`);
                });
                wrapper.querySelector('.remove-dependente').addEventListener('click', () => {
                    wrapper.remove();
                });
                container.appendChild(wrapper);
                index += 1;
            }

            addBtn.addEventListener('click', addDependente);

            // inicia com um dependente vazio
            addDependente();
        })();
    </script>
@endsection
