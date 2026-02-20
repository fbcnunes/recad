@extends('layouts.app')

@section('title', 'Admin - Dashboard')

@section('content')
    <div class="grid grid-2" style="margin-bottom:16px;">
        <div class="card">
            <h2 class="section-title">Total de servidores</h2>
            <div style="font-size:32px; font-weight:700;">{{ number_format($totalServidores, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <h2 class="section-title">Recadastramentos concluídos</h2>
            <div style="font-size:32px; font-weight:700;">{{ number_format($totalConcluidos, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <h2 class="section-title">Pendentes</h2>
            <div style="font-size:32px; font-weight:700;">{{ number_format($totalPendentes, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <h2 class="section-title">Admins concedidos manualmente</h2>
            <div style="font-size:32px; font-weight:700;">{{ number_format($totalAdminsManuais, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Atalhos</h2>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a class="btn" href="{{ route('admin.concluidos') }}">Ver concluídos</a>
            <a class="btn secondary" href="{{ route('admin.admins') }}">Gerenciar administradores</a>
        </div>
    </div>
@endsection
