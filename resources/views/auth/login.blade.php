<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Recad</title>
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0;
            font-family: "IBM Plex Sans", "Work Sans", system-ui, -apple-system, sans-serif;
            background: radial-gradient(1200px 600px at 80% -10%, #fde68a33, transparent),
                        radial-gradient(1000px 600px at -10% 10%, #99f6e433, transparent),
                        #f6f3ee;
            display: grid;
            place-items: center;
            min-height: 100vh;
            color: #1a1a1a;
        }
        .card {
            width: min(420px, 92vw);
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .title { font-size: 20px; font-weight: 700; margin: 0 0 6px; }
        .muted { color: #6b7280; font-size: 14px; margin-bottom: 16px; }
        label { font-size: 13px; color: #6b7280; }
        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-top: 6px;
            font-size: 14px;
        }
        .btn {
            width: 100%;
            margin-top: 16px;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #0f766e;
            background: #0f766e;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        .errors {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #7f1d1d;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <form class="card" method="post" action="{{ route('login.post') }}">
        @csrf
        <h1 class="title">Acesso ao Recad</h1>
        <p class="muted">Use seu usuário do AD ou local.</p>

        @if($errors->any())
            <div class="errors">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif


        <div>
            <label>Usuário</label>
            <input name="username" value="{{ old('username') }}" required>
        </div>
        <div style="margin-top:12px;">
            <label>Senha</label>
            <input type="password" name="password" required>
        </div>

        <button class="btn" type="submit">Entrar</button>
    </form>
</body>
</html>
