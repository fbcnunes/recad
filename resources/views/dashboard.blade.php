@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="grid grid-2">
        <div class="card">
            <h2 class="section-title">Bem-vindo ao Recad</h2>
            <p class="muted">Sistema de cadastro de servidores, documentos e dependentes.</p>
            <div style="margin-top:16px; display:flex; gap:12px; flex-wrap:wrap;">
                <a class="btn" href="{{ route('servidores.create') }}">Novo cadastro</a>
                <a class="btn secondary" href="{{ route('servidores.index') }}">Ver servidores</a>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Atalhos rápidos</h2>
            <div class="grid">
                <div>
                    <div class="badge">Cadastros</div>
                    <p class="muted">Crie e mantenha os registros completos dos servidores.</p>
                </div>
                <div>
                    <div class="badge">Documentos</div>
                    <p class="muted">Centralize RG, CPF, CNH, CTPS e certidões.</p>
                </div>
                <div>
                    <div class="badge">Dependentes</div>
                    <p class="muted">Controle benefícios e vínculos familiares.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
