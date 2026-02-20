<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Recad')</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f3ee;
            --panel: #ffffff;
            --text: #1a1a1a;
            --muted: #6b7280;
            --primary: #0f766e;
            --primary-dark: #115e59;
            --border: #e5e7eb;
            --accent: #f59e0b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "IBM Plex Sans", "Work Sans", system-ui, -apple-system, sans-serif;
            background: radial-gradient(1200px 600px at 80% -10%, #fde68a33, transparent),
                        radial-gradient(1000px 600px at -10% 10%, #99f6e433, transparent),
                        var(--bg);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        .app {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background: #0f172a;
            color: #e2e8f0;
            padding: 24px 16px;
        }
        .brand {
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
        }
        .nav a {
            display: block;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.04);
        }
        .nav a:hover {
            background: rgba(255, 255, 255, 0.12);
        }
        .content {
            padding: 32px;
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .grid {
            display: grid;
            gap: 16px;
        }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid var(--primary);
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            font-weight: 600;
        }
        .btn.secondary {
            background: transparent;
            color: var(--primary);
        }
        .muted { color: var(--muted); }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 12px;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        label { font-size: 13px; color: var(--muted); }
        input, select, textarea {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .table th, .table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }
        .badge {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 999px;
            background: #e2e8f0;
            font-size: 12px;
        }
        @media (max-width: 980px) {
            .app { grid-template-columns: 1fr; }
            .sidebar { position: sticky; top: 0; z-index: 10; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="app">
    @php
        $currentUser = auth()->user();
        $currentUserIsAdmin = $currentUser?->isAdmin() ?? false;
    @endphp
    <aside class="sidebar">
        <div class="brand">Recad</div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}">Meu cadastro</a>
            @if($currentUserIsAdmin)
                <a href="{{ route('admin.dashboard') }}">Admin: Dashboard</a>
                <a href="{{ route('admin.concluidos') }}">Admin: Concluídos</a>
                <a href="{{ route('admin.admins') }}">Admin: Administradores</a>
            @endif
        </nav>
        <div style="margin-top:24px; font-size:13px; color:#94a3b8;">
            <div>Usuário: {{ $currentUser->name ?? '—' }} @if(session('ldap_pager'))<span style="color:#cbd5f5;">({{ session('ldap_pager') }})</span>@endif</div>
            <div>Perfil LDAP: {{ $currentUser->role ?? '—' }}</div>
            <div>Acesso admin: {{ $currentUserIsAdmin ? 'sim' : 'não' }}</div>
            <form method="post" action="{{ route('logout') }}" style="margin-top:12px;">
                @csrf
                <button class="btn secondary" type="submit" style="width:100%;">Sair</button>
            </form>
        </div>
    </aside>
    <main class="content">
        @if(session('status'))
            <div class="card flash-message" style="border-color:#86efac; background:#f0fdf4; margin-bottom:16px;">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="card flash-message" style="border-color:#fca5a5; background:#fef2f2; margin-bottom:16px;">
                <strong>Erros no formulário:</strong>
                <ul style="margin:8px 0 0 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>
<script>
    (function () {
        const flashes = document.querySelectorAll('.flash-message');
        if (!flashes.length) return;
        window.setTimeout(() => {
            flashes.forEach(el => {
                el.style.transition = 'opacity 250ms ease, transform 250ms ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-6px)';
                window.setTimeout(() => el.remove(), 260);
            });
        }, 5000);
    })();
</script>
<script>
    (function () {
        // Uppercase text inputs on blur for data consistency (display and stored value).
        // Excludes email/password/date/number/tel and read-only/disabled fields.
        const shouldUppercase = (el) => {
            if (!el) return false;
            if (el.disabled) return false;
            if (el.readOnly) return false;
            if (el.tagName === 'TEXTAREA') return true;
            if (el.tagName !== 'INPUT') return false;
            const t = (el.getAttribute('type') || 'text').toLowerCase();
            if (t === 'email' || t === 'password' || t === 'date' || t === 'number' || t === 'tel') return false;
            return t === 'text' || t === 'search' || t === '';
        };

        document.addEventListener('focusin', (e) => {
            const el = e.target;
            if (!shouldUppercase(el)) return;
            el.style.textTransform = 'uppercase';
        });

        document.addEventListener('blur', (e) => {
            const el = e.target;
            if (!shouldUppercase(el)) return;
            const v = el.value;
            if (!v) return;
            const up = v.toLocaleUpperCase('pt-BR');
            if (up !== v) el.value = up;
        }, true);
    })();
</script>
</body>
</html>
