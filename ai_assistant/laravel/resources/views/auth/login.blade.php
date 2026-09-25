<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant - Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0F1117; color: #e0e0e0; }
        .card { background: #1a1f2e; border: 1px solid #00FFFF20; border-radius: 12px; }
        .input-dark { background: #0F1117; border: 1px solid #2a3040; color: #e0e0e0; width: 100%; padding: 10px 14px; border-radius: 8px; font-size: 14px; }
        .input-dark:focus { border-color: #00FFFF; outline: none; }
        .btn-cyan { background: #00FFFF; color: #0F1117; font-weight: 600; padding: 10px; border-radius: 8px; width: 100%; cursor: pointer; border: 0; }
        .btn-cyan:hover { background: #00CCCC; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="card p-8 w-full max-w-sm">
        <h1 class="text-2xl font-bold text-center mb-1" style="color: #00FFFF;">Assistant</h1>
        <p class="text-xs text-gray-500 text-center mb-6">Connectez-vous</p>

        @if ($errors->any())
            <div class="bg-red-400/10 border border-red-400/30 rounded-lg p-3 mb-4 text-sm text-red-400">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/login" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs text-gray-400 block mb-1">Nom d'utilisateur</label>
                <input type="text" name="name" class="input-dark" placeholder="ADMIN ou MKTANGER" required autofocus>
            </div>
            <div>
                <label class="text-xs text-gray-400 block mb-1">Mot de passe</label>
                <input type="password" name="password" class="input-dark" placeholder="••••••" required>
            </div>
            <button type="submit" class="btn-cyan">Se connecter</button>
        </form>
    </div>
</body>
</html>
