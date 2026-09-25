@extends('layouts.app')
@section('title', 'Employes')
@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
        <h2 class="text-2xl font-bold">Employes</h2>
        @if(auth()->user()?->isAdmin())
        <div class="flex gap-2">
            <button class="emp-tab-btn active px-3 py-1.5 text-sm rounded text-cyan-400 bg-cyan-400/10 font-semibold" data-emp-tab="tabAll" onclick="switchEmpTab('tabAll')">Tous</button>
            <button class="emp-tab-btn px-3 py-1.5 text-sm rounded text-gray-500 hover:text-gray-300" data-emp-tab="tabNonQual" onclick="switchEmpTab('tabNonQual')">Non qualifies</button>
        </div>
        @endif
    </div>
    <div class="text-xs text-gray-500 mb-4">Total : <span class="text-cyan-400 font-semibold">{{ count($employees) }}</span> employe(s)</div>

    <!-- Search -->
    <div class="mb-4">
        <input id="search" type="text" class="input-dark rounded-lg px-4 py-2.5 w-full text-sm" placeholder="Chercher par nom ou matricule...">
    </div>

    <!-- Tab: Tous -->
    <div id="tabAll" class="emp-tab-content">
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-800 bg-black/20">
                        <th class="text-left py-3 px-3">Matricule</th>
                        <th class="text-left py-3 px-3">Nom</th>
                        <th class="text-left py-3 px-3">Processus</th>
                        <th class="text-left py-3 px-3">UAP</th>
                        <th class="text-center py-3 px-2">Alerte</th>
                        <th class="text-center py-3 px-2">Valides</th>
                        <th class="text-center py-3 px-2">Requalifier</th>
                        <th class="text-center py-3 px-2">En cours</th>
                        <th class="text-center py-3 px-2">Suspendus</th>
                    </tr>
                </thead>
                <tbody id="empTable">
                    @foreach($employees as $emp)
                    <tr class="emp-row border-b border-gray-800/50 hover:bg-white/5" data-name="{{ strtolower($emp['name']) }}" data-mat="{{ $emp['matricule'] }}">
                        <td class="py-2.5 px-3">
                            <a href="/employees/{{ $emp['matricule'] }}" class="text-cyan-400 hover:underline font-mono">{{ $emp['matricule'] }}</a>
                        </td>
                        <td class="py-2.5 px-3">{{ $emp['name'] }}</td>
                        <td class="py-2.5 px-3 text-gray-400">{{ $emp['processus'] ?? '-' }}</td>
                        <td class="py-2.5 px-3 text-gray-400">{{ $emp['uap'] ?? '-' }}</td>
                        <td class="py-2.5 px-2 text-center">
                            @if($emp['suspended'] > 0)
                                <span class="badge" style="background:#ff004040;color:#ff6b6b;">ALERTE</span>
                            @elseif($emp['requalify'] > 0 || $emp['prevu'] > 0)
                                <span class="badge" style="background:#ffa50030;color:#ffa500;">SURVEILLANCE</span>
                            @else
                                <span class="text-gray-600">-</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-2 text-center text-green-400">{{ $emp['valid'] }}</td>
                        <td class="py-2.5 px-2 text-center text-yellow-400">{{ $emp['requalify'] }}</td>
                        <td class="py-2.5 px-2 text-center text-cyan-400">{{ $emp['en_cours'] }}</td>
                        <td class="py-2.5 px-2 text-center text-red-400">{{ $emp['suspended'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Non qualifies (admin only) -->
    @if(auth()->user()?->isAdmin())
    <div id="tabNonQual" class="emp-tab-content hidden">
        <div class="text-xs text-gray-500 mb-3"><span class="text-yellow-400 font-semibold">{{ count($nonQualifies) }}</span> employe(s) sans aucun PPS (0 partout)</div>
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-800 bg-black/20">
                        <th class="text-left py-3 px-3">Matricule</th>
                        <th class="text-left py-3 px-3">Nom</th>
                        <th class="text-left py-3 px-3">Processus</th>
                        <th class="text-left py-3 px-3">UAP</th>
                        <th class="text-center py-3 px-2">Valides</th>
                        <th class="text-center py-3 px-2">Requalifier</th>
                        <th class="text-center py-3 px-2">Suspendus</th>
                        <th class="text-center py-3 px-2">En cours</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nonQualifies as $emp)
                    <tr class="emp-nq-row border-b border-gray-800/50 hover:bg-white/5">
                        <td class="py-2.5 px-3">
                            <a href="/employees/{{ $emp['matricule'] }}" class="text-cyan-400 hover:underline font-mono">{{ $emp['matricule'] }}</a>
                        </td>
                        <td class="py-2.5 px-3">{{ $emp['name'] }}</td>
                        <td class="py-2.5 px-3 text-gray-400">{{ $emp['processus'] ?? '-' }}</td>
                        <td class="py-2.5 px-3 text-gray-400">{{ $emp['uap'] ?? '-' }}</td>
                        <td class="py-2.5 px-2 text-center text-green-400">{{ $emp['valid'] }}</td>
                        <td class="py-2.5 px-2 text-center text-yellow-400">{{ $emp['requalify'] }}</td>
                        <td class="py-2.5 px-2 text-center text-red-400">{{ $emp['suspended'] }}</td>
                        <td class="py-2.5 px-2 text-center text-cyan-400">{{ $emp['en_cours'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="py-4 text-center text-gray-500">Tous les employes ont au moins une qualification</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function switchEmpTab(tabId) {
    document.querySelectorAll('.emp-tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(tabId).classList.remove('hidden');
    document.querySelectorAll('.emp-tab-btn').forEach(btn => {
        btn.classList.remove('text-cyan-400', 'bg-cyan-400/10', 'font-semibold');
        btn.classList.add('text-gray-500');
        if (btn.dataset.empTab === tabId) {
            btn.classList.add('text-cyan-400', 'bg-cyan-400/10', 'font-semibold');
            btn.classList.remove('text-gray-500');
        }
    });
}

document.getElementById('search').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.emp-row').forEach(row => {
        row.style.display = row.dataset.name.includes(q) || row.dataset.mat.includes(q) ? '' : 'none';
    });
});
</script>
@endpush
