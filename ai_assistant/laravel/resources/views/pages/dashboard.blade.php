@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="max-w-7xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Dashboard</h2>

    <!-- Stats cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 mb-8">
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-green-400">{{ $totals['valides'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Valides [OK]</div>
        </div>
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-yellow-400">{{ $totals['requalifier'] }}</div>
            <div class="text-xs text-gray-400 mt-1">A requalifier [!]</div>
        </div>
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-purple-400">{{ $totals['prevu'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Prevus [~]</div>
        </div>
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-cyan-400">{{ $totals['en_cours'] }}</div>
            <div class="text-xs text-gray-400 mt-1">En cours [>]</div>
        </div>
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-red-400">{{ $totals['suspendus'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Suspendus [X]</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- PPS Distribution Chart -->
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-300 mb-4">Distribution des PPS</h3>
            <div id="ppsChart"></div>
        </div>

        <!-- Alertes (ex-risques) -->
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-300 mb-4">Alertes</h3>
            <div id="alertesContent" class="text-sm">
                @php $alerteCount = 0; @endphp
                @foreach($data as $emp)
                    @if($emp['suspended'] > 0)
                        @php $alerteCount++; @endphp
                    @endif
                @endforeach
                @if($alerteCount > 0)
                    <div class="flex items-center gap-3 p-3 bg-red-400/10 border border-red-400/30 rounded-lg mb-2">
                        <span class="text-2xl">&#9888;</span>
                        <div>
                            <div class="text-red-400 font-semibold">{{ $alerteCount }} employe(s) avec suspension(s)</div>
                            <div class="text-gray-400 text-xs mt-1">Action requise : au moins un PPS suspendu</div>
                        </div>
                    </div>
                @else
                    <div class="text-gray-500">Aucune alerte</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats summary card -->
    <div class="card p-5 mb-8">
        <h3 class="text-sm font-semibold text-gray-300 mb-3">Resume du jeu de donnees</h3>
        <div class="text-3xl font-bold text-white">{{ $meta['rows'] ?? 0 }} employes</div>
        <div class="text-xs text-gray-400 mt-1">{{ $meta['columns_count'] ?? 0 }}-cols schema</div>
    </div>

    <!-- Suspended employees table -->
    <div class="card p-5 mb-8">
        <h3 class="text-sm font-semibold text-gray-300 mb-3">Employes avec PPS suspendus</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-800">
                        <th class="text-left py-2 px-2">#</th>
                        <th class="text-left py-2 px-2">Nom</th>
                        <th class="text-left py-2 px-2">Matricule</th>
                        <th class="text-left py-2 px-2">PPS Suspendus</th>
                        <th class="text-left py-2 px-2">Date de Validation</th>
                    </tr>
                </thead>
                <tbody>
                    @php $count = 0; @endphp
                    @foreach($data as $emp)
                        @if($emp['suspended'] > 0)
                            @php $count++; @endphp
                            <tr class="border-b border-gray-800/50 hover:bg-white/5">
                                <td class="py-2 px-2 text-gray-500">{{ $count }}</td>
                                <td class="py-2 px-2">
                                    <a href="/employees/{{ $emp['matricule'] }}" class="text-cyan-400 hover:underline">
                                        {{ $emp['name'] }}
                                    </a>
                                </td>
                                <td class="py-2 px-2">{{ $emp['matricule'] }}</td>
                                <td class="py-2 px-2 text-red-400">{{ implode(', ', array_slice($emp['suspended_pps'], 0, 3)) }}</td>
                                <td class="py-2 px-2 text-gray-400 text-xs">
                                    @php
                                        $dates = collect($emp['suspended_pps_dates'] ?? []);
                                    @endphp
                                    @foreach($dates->take(3) as $d)
                                        <span class="block {{ $d['date'] ? '' : 'text-gray-600 italic' }}">{{ $d['date'] ? date('d/m/Y', strtotime($d['date'])) : 'N/A' }}</span>
                                    @endforeach
                                    @if($dates->count() > 3)
                                        <span class="text-gray-600">+{{ $dates->count() - 3 }} autres</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    @if($count === 0)
                        <tr><td colspan="5" class="py-4 text-center text-gray-500">Aucun employe avec PPS suspendu</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dynamique PPS table -->
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-gray-300 mb-3">Apercu des PPS</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-800">
                        <th class="text-left py-2 px-2">PPS</th>
                        <th class="text-center py-2 px-2">Valides</th>
                        <th class="text-center py-2 px-2">A requalifier</th>
                        <th class="text-center py-2 px-2">Prevus</th>
                        <th class="text-center py-2 px-2">En cours</th>
                        <th class="text-center py-2 px-2">Suspendus</th>
                        <th class="text-center py-2 px-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ppsAgg = [];
                        foreach($data as $emp) {
                            $seen = [];
                            foreach($emp['valid_pps'] ?? [] as $p) { $ppsAgg[$p]['valide'] = ($ppsAgg[$p]['valide'] ?? 0) + 1; $seen[] = $p; }
                            foreach($emp['requalify_pps'] ?? [] as $p) { $ppsAgg[$p]['requalifier'] = ($ppsAgg[$p]['requalifier'] ?? 0) + 1; }
                            foreach($emp['prevu_pps'] ?? [] as $p) { $ppsAgg[$p]['prevu'] = ($ppsAgg[$p]['prevu'] ?? 0) + 1; }
                            foreach($emp['en_cours_pps'] ?? [] as $p) { $ppsAgg[$p]['en_cours'] = ($ppsAgg[$p]['en_cours'] ?? 0) + 1; }
                            foreach($emp['suspended_pps'] ?? [] as $p) { $ppsAgg[$p]['suspendu'] = ($ppsAgg[$p]['suspendu'] ?? 0) + 1; }
                        }
                        ksort($ppsAgg);
                    @endphp
                    @foreach($ppsAgg as $pps => $stats)
                        @php
                            $v = $stats['valide'] ?? 0;
                            $r = $stats['requalifier'] ?? 0;
                            $p = $stats['prevu'] ?? 0;
                            $e = $stats['en_cours'] ?? 0;
                            $s = $stats['suspendu'] ?? 0;
                            $total = $v + $r + $p + $e + $s;
                        @endphp
                        <tr class="border-b border-gray-800/50 hover:bg-white/5">
                            <td class="py-2 px-2 text-cyan-400 font-mono text-xs">{{ $pps }}</td>
                            <td class="py-2 px-2 text-center text-green-400">{{ $v }}</td>
                            <td class="py-2 px-2 text-center text-yellow-400">{{ $r }}</td>
                            <td class="py-2 px-2 text-center text-purple-400">{{ $p }}</td>
                            <td class="py-2 px-2 text-center text-cyan-400">{{ $e }}</td>
                            <td class="py-2 px-2 text-center text-red-400">{{ $s }}</td>
                            <td class="py-2 px-2 text-center text-gray-300">{{ $total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
new ApexCharts(document.getElementById('ppsChart'), {
    chart: { type: 'donut', height: 220, foreColor: '#8b949e',
        events: { dataPointSelection: (e, ctx, config) => { window.location = '/pps'; } }
    },
    series: [{{ $totals['valides'] }}, {{ $totals['requalifier'] }}, {{ $totals['prevu'] }}, {{ $totals['en_cours'] }}, {{ $totals['suspendus'] }}],
    labels: ['Valides', 'A requalifier', 'Prevus', 'En cours', 'Suspendus'],
    colors: ['#00E676', '#FFAB00', '#B388FF', '#18FFFF', '#FF5252'],
    legend: { position: 'bottom', labels: { colors: '#8b949e' } },
    dataLabels: { enabled: true, style: { colors: ['#fff'], fontSize: '12px', fontFamily: 'Inter, sans-serif' } },
    plotOptions: { pie: { donut: { size: '55%' } } },
    responsive: [{ breakpoint: 768, options: { chart: { height: 250 } } }]
}).render();
</script>
@endpush
