@extends('layouts.app')

@section('title', 'Admin - Administradores')

@section('content')
    <div class="card" style="margin-bottom:16px;">
        <h2 class="section-title">Administradores iniciais (configuração)</h2>
        @if(empty($configuredInitialAdmins))
            <p class="muted">Nenhum administrador inicial configurado.</p>
        @else
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @foreach($configuredInitialAdmins as $username)
                    <span class="badge">{{ $username }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <h2 class="section-title">Gerenciar administradores</h2>

        <form method="get" action="{{ route('admin.admins') }}" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
            <input name="q" value="{{ $q }}" placeholder="Buscar por nome, username ou email" style="min-width:280px;">
            <button class="btn" type="submit">Buscar</button>
            <a class="btn secondary" href="{{ route('admin.admins') }}">Limpar</a>
        </form>

        <p class="muted" style="margin-top:0;">Somente usuários que já fizeram login no sistema aparecem aqui.</p>

        <div style="overflow:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Origem do acesso admin</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $isInitial = $user->isConfiguredInitialAdmin();
                            $isManual = $user->adminGrant !== null;
                            $isRoleAdmin = $user->role === 'admin';
                            $isAdmin = $user->isAdmin();
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $user->username ?: '—' }}</strong>
                                <div class="muted">{{ $user->name }}</div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if(!$isAdmin)
                                    <span class="badge">Sem acesso admin</span>
                                @endif
                                @if($isInitial)
                                    <span class="badge">Inicial (.env)</span>
                                @endif
                                @if($isManual)
                                    <span class="badge">Manual</span>
                                @endif
                                @if($isRoleAdmin)
                                    <span class="badge">LDAP role=admin</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <form method="post" action="{{ route('admin.admins.grant', $user) }}">
                                        @csrf
                                        <button class="btn" type="submit" {{ $isManual ? 'disabled' : '' }}>Conceder</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.admins.revoke', $user) }}">
                                        @csrf
                                        <button class="btn secondary" type="submit" {{ !$isManual ? 'disabled' : '' }}>Revogar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">Nenhum usuário encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $users->links() }}
        </div>
    </div>
@endsection
