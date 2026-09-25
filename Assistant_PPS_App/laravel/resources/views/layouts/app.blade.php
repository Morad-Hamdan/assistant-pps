<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant - @yield('title', 'Tableau de bord')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0F1117; color: #e0e0e0; }
        .sidebar { background: #161B22; border-right: 1px solid #1e2530; }
        .sidebar a { color: #8b949e; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { color: #00FFFF; background: rgba(0,255,255,0.05); }
        .card { background: #1a1f2e; border: 1px solid #00FFFF20; border-radius: 12px; }
        .card:hover { border-color: #00FFFF40; }
        .stat-value { font-size: 1.8rem; font-weight: 700; }
        .badge { padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-high { background: #ff004040; color: #ff6b6b; }
        .badge-medium { background: #ffa50030; color: #ffa500; }
        .badge-low { background: #00ff8830; color: #00ff88; }
        .btn-cyan { background: #00FFFF; color: #0F1117; font-weight: 600; }
        .btn-cyan:hover { background: #00CCCC; }
        .input-dark { background: #0F1117; border: 1px solid #2a3040; color: #e0e0e0; }
        .input-dark:focus { border-color: #00FFFF; outline: none; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0F1117; }
        ::-webkit-scrollbar-thumb { background: #2a3040; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #3a4050; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -100%; top: 0; bottom: 0; width: 260px; z-index: 50; transition: left 0.3s; }
            .sidebar.open { left: 0; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 40; }
            .sidebar-overlay.show { display: block; }
            .stat-value { font-size: 1.2rem; }
        }
    </style>
</head>
<body class="flex h-screen">
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Hamburger (mobile) -->
    <button onclick="toggleSidebar()" class="fixed top-4 left-4 z-50 md:hidden bg-[#00FFFF] text-black p-2 rounded-lg text-lg leading-none" style="width:36px;height:36px;">☰</button>

    <!-- Sidebar -->
    <aside class="sidebar w-64 flex-shrink-0 p-5 flex flex-col md:relative md:left-auto">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="text-2xl font-bold" style="color: #00FFFF;">Assistant</h1>
            <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-white text-xl leading-none">&times;</button>
        </div>
        <nav class="flex flex-col space-y-1 flex-1">
            @if(auth()->user()?->isAdmin())
            <a href="/" class="px-4 py-2.5 rounded-lg text-sm {{ request()->is('/') ? 'active' : '' }}">
                📊 Dashboard
            </a>
            @endif
            <a href="/employees" class="px-4 py-2.5 rounded-lg text-sm {{ request()->is('employees*') ? 'active' : '' }}">
                👥 Employes
            </a>
            @if(auth()->user()?->isAdmin())
            <a href="/pps" class="px-4 py-2.5 rounded-lg text-sm {{ request()->is('pps') ? 'active' : '' }}">
                📋 PPS
            </a>
            @endif
        </nav>
        <div class="text-xs text-gray-500 mt-4 mb-2">
            @auth
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="/logout" class="inline">
                @csrf
                <button type="submit" class="text-gray-600 hover:text-red-400 ml-2">Deconnexion</button>
            </form>
            @endauth
        </div>
        <div class="text-xs text-gray-600 mt-auto pt-4 border-t border-gray-800">
            v1.0 &middot; FastAPI + Laravel
        </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-y-auto p-4 md:p-6">
        @yield('content')
    </main>

    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('open');
        document.querySelector('.sidebar-overlay').classList.toggle('show');
    }
    document.querySelectorAll('.sidebar a').forEach(a => {
        a.addEventListener('click', () => {
            if (window.innerWidth < 768) toggleSidebar();
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
