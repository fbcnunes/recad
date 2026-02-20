@extends('layouts.app')

@section('title', 'Admin - Concluídos')

@section('content')
    <div class="card" style="margin-bottom:16px;">
        <h2 class="section-title">Filtros</h2>
        <form method="get" action="{{ route('admin.concluidos') }}" class="grid grid-3" style="align-items:end;">
            <div class="field">
                <label>Busca (nome, matrícula, email)</label>
                <input name="q" value="{{ $filters['q'] }}">
            </div>
            <div class="field">
                <label>Concluído de</label>
                <input type="date" name="concluido_de" value="{{ $filters['concluido_de'] }}">
            </div>
            <div class="field">
                <label>Concluído até</label>
                <input type="date" name="concluido_ate" value="{{ $filters['concluido_ate'] }}">
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; grid-column:1 / -1;">
                <button class="btn" type="submit">Aplicar</button>
                <a class="btn secondary" href="{{ route('admin.concluidos') }}">Limpar</a>
                <a class="btn secondary" href="{{ route('admin.concluidos.export.csv', request()->query()) }}">Exportar CSV</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="section-title">Concluídos ({{ $servidores->total() }})</h2>

        <div style="overflow:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Celular</th>
                        <th>Concluído em</th>
                        <th>Concluído por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($servidores as $s)
                        <tr>
                            <td>{{ $s->matricula }}</td>
                            <td>{{ $s->nome }}</td>
                            <td>{{ $s->email }}</td>
                            <td>{{ $s->celular }}</td>
                            <td>{{ optional($s->recadastramento_concluido_em)->format('d/m/Y H:i') }}</td>
                            <td>{{ $s->concluidoPor?->username ?? $s->concluidoPor?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">Nenhum registro encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $servidores->links() }}
        </div>
    </div>
@endsection
