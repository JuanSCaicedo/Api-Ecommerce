<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso Telescope</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #0f172a;
            --panel: rgba(15, 23, 42, 0.9);
            --border: rgba(148, 163, 184, 0.24);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --accent-2: #22c55e;
            --danger: #fb7185;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.2), transparent 30%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.18), transparent 28%),
                linear-gradient(160deg, #020617 0%, #0f172a 48%, #111827 100%);
            color: var(--text);
            padding: 24px;
        }

        .card {
            width: min(100%, 420px);
            padding: 32px;
            border: 1px solid var(--border);
            border-radius: 24px;
            background: var(--panel);
            box-shadow: 0 30px 80px rgba(2, 6, 23, 0.45);
            backdrop-filter: blur(18px);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
            line-height: 1.1;
        }

        p {
            margin: 0 0 24px;
            color: var(--muted);
        }

        .field {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #cbd5e1;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.72);
            color: var(--text);
            outline: none;
        }

        input:focus {
            border-color: rgba(56, 189, 248, 0.65);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.12);
        }

        .error {
            margin: 0 0 16px;
            color: #fecaca;
            font-size: 14px;
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 20px;
        }

        .button {
            appearance: none;
            border: 0;
            border-radius: 14px;
            padding: 12px 18px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #00111a;
            font-weight: 700;
            cursor: pointer;
        }

        .hint {
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Acceso a Telescope</h1>
        <p>Ingresa con tu usuario administrativo para abrir el panel en producción.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('telescope.login') }}">
            @csrf
            <div class="field">
                <label for="email">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" required>
            </div>

            <label class="hint" style="display:flex; align-items:center; gap:8px; margin-top: 4px;">
                <input type="checkbox" name="remember" value="1" style="width:auto; margin:0;">
                Recordarme
            </label>

            <div class="actions">                
                <button class="button" type="submit">Entrar</button>
            </div>
        </form>
    </main>
</body>
</html>