@extends('layouts.app')

@section('title', 'Servidores')

@section('content')
    <div class="card" style="margin-bottom:16px;">
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
            <h2 class="section-title" style="margin:0;">Servidores</h2>
            <a class="btn" href="{{ route('servidores.create') }}">Novo cadastro</a>
        </div>
        <form method="get" action="{{ route('servidores.index') }}" style="margin-top:12px; display:flex; gap:12px; flex-wrap:wrap;">
            <input type="text" name="q" placeholder="Buscar por nome, matrícula ou CPF" value="{{ $q }}" style="min-width:280px;">
            <button class="btn secondary" type="submit">Buscar</button>
        </form>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Matrícula</th>
                    <th>CPF</th>
                    <th>E-mail</th>
                    <th>Celular</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servidores as $servidor)
                    <tr>
                        <td>{{ $servidor->nome }}</td>
                        <td>{{ $servidor->matricula }}</td>
                        <td>{{ optional($servidor->documentoPessoal)->cpf }}</td>
                        <td>{{ $servidor->email }}</td>
                        <td>{{ $servidor->celular }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">Nenhum servidor encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:16px;">
            {{ $servidores->links() }}
        </div>
    </div>
@endsection
