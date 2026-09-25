@extends('layouts.app')
@section('title', 'PPS')
@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Title + Tabs inline -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
        <h2 class="text-2xl font-bold">Suivi des PPS</h2>
        <div class="flex gap-2">
            <button class="tab-btn active px-3 py-1.5 text-sm rounded text-cyan-400 bg-cyan-400/10 font-semibold" data-tab="tabCharts" onclick="switchTab('tabCharts')">Graphiques</button>
            <button class="tab-btn px-3 py-1.5 text-sm rounded text-gray-500 hover:text-gray-300" data-tab="tabList" onclick="switchTab('tabList')">Liste PPS</button>
            <button class="tab-btn px-3 py-1.5 text-sm rounded text-gray-500 hover:text-gray-300" data-tab="tabRanking" onclick="switchTab('tabRanking')">Classement</button>
        </div>
    </div>

    <!-- Tab 1: Charts -->
    <div id="tabCharts" class="tab-content">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 mb-8">
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="valide" onclick="filterByStatus('valide')">
                <div class="stat-value text-green-400">{{ $totals['valides'] }}</div>
                <div class="text-xs text-gray-400 mt-1">Valides [OK]</div>
            </div>
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="requalifier" onclick="filterByStatus('requalifier')">
                <div class="stat-value text-yellow-400">{{ $totals['requalifier'] }}</div>
                <div class="text-xs text-gray-400 mt-1">A requalifier [!]</div>
            </div>
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="prevu" onclick="filterByStatus('prevu')">
                <div class="stat-value text-purple-400">{{ $totals['prevu'] }}</div>
                <div class="text-xs text-gray-400 mt-1">Prevus [~]</div>
            </div>
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="en_cours" onclick="filterByStatus('en_cours')">
                <div class="stat-value text-cyan-400">{{ $totals['en_cours'] }}</div>
                <div class="text-xs text-gray-400 mt-1">En cours [>]</div>
            </div>
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="suspendu" onclick="filterByStatus('suspendu')">
                <div class="stat-value text-red-400">{{ $totals['suspendus'] }}</div>
                <div class="text-xs text-gray-400 mt-1">Suspendus [X]</div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-300 mb-4">Repartition des PPS</h3>
                <div id="ppsDonut"></div>
            </div>
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-gray-300 mb-4">Statuts PPS (barres) <span class="text-xs text-gray-500">— clic sur une barre pour filtrer</span></h3>
                <div id="ppsBar"></div>
            </div>
        </div>
        <div id="ppsDetail" class="card p-5 mb-8 hidden">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white" id="detailTitle">—</h3>
                <button onclick="closeDetail()" class="text-gray-500 hover:text-white text-xl">&times;</button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4" id="detailStats"></div>
            <div id="detailEmployeeList" class="hidden">
                <h4 class="text-sm font-semibold text-gray-300 mb-2" id="detailListTitle">Employes</h4>
                <div id="detailListContent" class="max-h-60 overflow-y-auto space-y-1"></div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Liste PPS -->
    <div id="tabList" class="tab-content hidden">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 mb-8">
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="valide" onclick="filterByStatus('valide')">
                <div class="stat-value text-green-400">{{ $totals['valides'] }}</div>
                <div class="text-xs text-gray-400 mt-1">Valides [OK]</div>
            </div>
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="requalifier" onclick="filterByStatus('requalifier')">
                <div class="stat-value text-yellow-400">{{ $totals['requalifier'] }}</div>
                <div class="text-xs text-gray-400 mt-1">A requalifier [!]</div>
            </div>
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="prevu" onclick="filterByStatus('prevu')">
                <div class="stat-value text-purple-400">{{ $totals['prevu'] }}</div>
                <div class="text-xs text-gray-400 mt-1">Prevus [~]</div>
            </div>
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="en_cours" onclick="filterByStatus('en_cours')">
                <div class="stat-value text-cyan-400">{{ $totals['en_cours'] }}</div>
                <div class="text-xs text-gray-400 mt-1">En cours [>]</div>
            </div>
            <div class="card p-3 md:p-4 text-center cursor-pointer stat-card" data-status="suspendu" onclick="filterByStatus('suspendu')">
                <div class="stat-value text-red-400">{{ $totals['suspendus'] }}</div>
                <div class="text-xs text-gray-400 mt-1">Suspendus [X]</div>
            </div>
        </div>
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-300">Liste complete des PPS</h3>
                <div class="flex gap-2">
                    <button id="clearFilterBtn" onclick="clearFilter()" class="text-xs text-gray-500 hover:text-white hidden px-3 py-1 border border-gray-700 rounded">Effacer filtre</button>
                </div>
            </div>
            <div class="mb-3">
                <input id="ppsSearch" type="text" class="input-dark rounded-lg px-4 py-2 w-full text-sm" placeholder="Chercher un PPS...">
            </div>
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
                    <tbody id="ppsTable">
                        @foreach($ppsList as $pps)
                        <tr class="pps-row border-b border-gray-800/50 hover:bg-white/5" data-name="{{ strtolower($pps['nom']) }}">
                            <td class="py-2 px-2">
                                <button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}')" class="text-cyan-400 hover:underline font-mono text-xs text-left cursor-pointer bg-transparent border-0 p-0">
                                    {{ $pps['nom'] }}
                                </button>
                            </td>
                            <td class="py-2 px-2 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','valide')" class="stat-btn text-green-400">{{ $pps['valide'] ?? 0 }}</button></td>
                            <td class="py-2 px-2 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','requalifier')" class="stat-btn text-yellow-400">{{ $pps['requalifier'] ?? 0 }}</button></td>
                            <td class="py-2 px-2 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','prevu')" class="stat-btn text-purple-400">{{ $pps['prevu'] ?? 0 }}</button></td>
                            <td class="py-2 px-2 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','en_cours')" class="stat-btn text-cyan-400">{{ $pps['en_cours'] ?? 0 }}</button></td>
                            <td class="py-2 px-2 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','suspendu')" class="stat-btn text-red-400">{{ $pps['suspendu'] ?? 0 }}</button></td>
                            <td class="py-2 px-2 text-center text-gray-300">{{ ($pps['valide']??0) + ($pps['requalifier']??0) + ($pps['prevu']??0) + ($pps['en_cours']??0) + ($pps['suspendu']??0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div id="ppsDetailList" class="card p-5 mt-4 hidden">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white" id="detailListTitle2">—</h3>
                <button onclick="closeDetailList()" class="text-gray-500 hover:text-white text-xl">&times;</button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4" id="detailStatsList"></div>
            <div id="detailEmployeeListList" class="hidden">
                <h4 class="text-sm font-semibold text-gray-300 mb-2" id="detailListTitle3">Employes</h4>
                <div id="detailListContentList" class="max-h-60 overflow-y-auto space-y-1"></div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Classement -->
    <div id="tabRanking" class="tab-content hidden">
        <div class="card p-5 mb-6">
            <h3 class="text-sm font-semibold text-gray-300 mb-4">Top 15 des PPS les plus attribues</h3>
            <div style="height: 480px; overflow-y: auto;" class="pr-2">
                <div id="ppsValideChart"></div>
            </div>
        </div>
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h3 class="text-sm font-semibold text-gray-300">Classement complet des PPS</h3>
                <div class="flex items-center gap-3">
                    <div class="flex gap-2 flex-wrap">
                        <button onclick="filterByStatus('valide')" class="text-xs px-2 py-1 rounded" style="color:#00E676; border:1px solid #00E67640;">Valides</button>
                        <button onclick="filterByStatus('requalifier')" class="text-xs px-2 py-1 rounded" style="color:#FFAB00; border:1px solid #FFAB0040;">Requalifier</button>
                        <button onclick="filterByStatus('prevu')" class="text-xs px-2 py-1 rounded" style="color:#B388FF; border:1px solid #B388FF40;">Prevu</button>
                        <button onclick="filterByStatus('en_cours')" class="text-xs px-2 py-1 rounded" style="color:#18FFFF; border:1px solid #18FFFF40;">En cours</button>
                        <button onclick="filterByStatus('suspendu')" class="text-xs px-2 py-1 rounded" style="color:#FF5252; border:1px solid #FF525240;">Suspendus</button>
                    </div>
                    <span class="text-xs text-gray-500">{{ count($ppsValideRanking) }} PPS</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 border-b border-gray-800">
                            <th class="text-left py-2 px-1 w-8">#</th>
                            <th class="text-left py-2 px-2">PPS</th>
                            <th class="text-center py-2 px-1"><span style="color:#00E676">V</span></th>
                            <th class="text-center py-2 px-1"><span style="color:#FFAB00">R</span></th>
                            <th class="text-center py-2 px-1"><span style="color:#B388FF">P</span></th>
                            <th class="text-center py-2 px-1"><span style="color:#18FFFF">E</span></th>
                            <th class="text-center py-2 px-1"><span style="color:#FF5252">S</span></th>
                            <th class="text-center py-2 px-2">Total</th>
                            <th class="py-2 px-2">Distribution</th>
                        </tr>
                    </thead>
                    <tbody id="rankingTable">
                        @foreach($ppsValideRanking as $i => $pps)
                        @php
                            $v = (int)($pps['valide'] ?? 0);
                            $r = (int)($pps['requalifier'] ?? 0);
                            $p = (int)($pps['prevu'] ?? 0);
                            $e = (int)($pps['en_cours'] ?? 0);
                            $s = (int)($pps['suspendu'] ?? 0);
                            $total = $v + $r + $p + $e + $s;
                            $maxTotal = ($ppsValideRanking->max(fn($x) => ($x['valide']??0)+($x['requalifier']??0)+($x['prevu']??0)+($x['en_cours']??0)+($x['suspendu']??0))) ?: 1;
                            $pct = min(100, ($total / $maxTotal) * 100);
                        @endphp
                        <tr class="border-b border-gray-800/30 hover:bg-white/5 ranking-row">
                            <td class="py-1.5 px-1 text-gray-500 text-xs">{{ $i + 1 }}</td>
                            <td class="py-1.5 px-2">
                                <span class="text-cyan-300 hover:underline font-mono text-xs cursor-pointer" onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}')">{{ $pps['nom'] }}</span>
                            </td>
                            <td class="py-1.5 px-1 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','valide')" class="stat-btn text-green-400 text-xs font-mono">{{ $v }}</button></td>
                            <td class="py-1.5 px-1 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','requalifier')" class="stat-btn text-yellow-400 text-xs font-mono">{{ $r }}</button></td>
                            <td class="py-1.5 px-1 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','prevu')" class="stat-btn text-purple-400 text-xs font-mono">{{ $p }}</button></td>
                            <td class="py-1.5 px-1 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','en_cours')" class="stat-btn text-cyan-400 text-xs font-mono">{{ $e }}</button></td>
                            <td class="py-1.5 px-1 text-center"><button onclick="showPpsDetailList('{{ addslashes($pps['nom']) }}','suspendu')" class="stat-btn text-red-400 text-xs font-mono">{{ $s }}</button></td>
                            <td class="py-1.5 px-2 text-center text-gray-300 text-xs font-mono">{{ $total }}</td>
                            <td class="py-1.5 px-2">
                                <div class="flex items-center gap-1" style="height:14px;">
                                    @if($total > 0)
                                    <div class="flex w-full rounded-full overflow-hidden" style="height:10px;">
                                        <div style="width:{{ ($v/$total)*100 }}%; background:#00E676;"></div>
                                        <div style="width:{{ ($r/$total)*100 }}%; background:#FFAB00;"></div>
                                        <div style="width:{{ ($p/$total)*100 }}%; background:#B388FF;"></div>
                                        <div style="width:{{ ($e/$total)*100 }}%; background:#18FFFF;"></div>
                                        <div style="width:{{ ($s/$total)*100 }}%; background:#FF5252;"></div>
                                    </div>
                                    @else
                                    <div class="w-full bg-gray-800 rounded-full" style="height:10px;"></div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Data ──
const colorMap = {
    valide: '#00E676', requalifier: '#FFAB00',
    prevu: '#B388FF', en_cours: '#18FFFF', suspendu: '#FF5252'
};
const colorDimMap = {
    valide: '#00E67630', requalifier: '#FFAB0030',
    prevu: '#B388FF30', en_cours: '#18FFFF30', suspendu: '#FF525230'
};
const labelMap = {
    valide: 'Valides', requalifier: 'A requalifier',
    prevu: 'Prevus', en_cours: 'En cours', suspendu: 'Suspendus'
};
const statusKeys = ['valide','requalifier','prevu','en_cours','suspendu'];
@php
$detailJson = [];
foreach($ppsList as $pps) {
    $d = ['nom' => $pps['nom']];
    foreach (['valide','requalifier','prevu','en_cours','suspendu'] as $s) {
        $d[$s] = (int)($pps[$s] ?? 0);
        $d[$s.'_emps'] = $pps[$s.'_emps'] ?? [];
    }
    $detailJson[] = $d;
}
@endphp
const ppsData = @json($detailJson);
let activeFilter = null; // 'valide'|'requalifier'|etc or null

// ── Charts ──
const totalData = [{{ $totals['valides'] }}, {{ $totals['requalifier'] }}, {{ $totals['prevu'] }}, {{ $totals['en_cours'] }}, {{ $totals['suspendus'] }}];
const chartLabels = ['Valides', 'A requalifier', 'Prevus', 'En cours', 'Suspendus'];
const chartColors = ['#00E676', '#FFAB00', '#B388FF', '#18FFFF', '#FF5252'];
const chartColorsDim = ['#00E67630', '#FFAB0030', '#B388FF30', '#18FFFF30', '#FF525230'];

window.ppsDonutChart = new ApexCharts(document.getElementById('ppsDonut'), {
    chart: { type: 'donut', height: 220, foreColor: '#8b949e',
        events: { dataPointSelection: (e, ctx, config) => filterByStatus(statusKeys[config.seriesIndex]) }
    },
    series: totalData,
    labels: chartLabels,
    colors: chartColors,
    legend: { position: 'bottom', labels: { colors: '#8b949e' } },
    dataLabels: { enabled: true, style: { colors: ['#fff'], fontSize: '12px', fontFamily: 'Inter, sans-serif' } },
    plotOptions: { pie: { donut: { size: '55%' } } },
    responsive: [{ breakpoint: 768, options: { chart: { height: 250 } } }]
});
window.ppsDonutChart.render();

window.ppsBarChart = new ApexCharts(document.getElementById('ppsBar'), {
    chart: { type: 'bar', height: 220, foreColor: '#8b949e',
        events: { dataPointSelection: (e, ctx, config) => filterByStatus(statusKeys[config.dataPointIndex]) },
        toolbar: { show: false }
    },
    series: [{ name: 'Nombre de PPS', data: totalData }],
    colors: chartColors,
    plotOptions: { bar: { distributed: true, borderRadius: 6, columnWidth: '60%' } },
    dataLabels: { enabled: true, offsetY: -20, style: { colors: ['#e0e0e0'], fontSize: '10px', fontFamily: 'Inter, sans-serif' } },
    xaxis: { categories: chartLabels, labels: { style: { colors: '#8b949e' } } },
    yaxis: { labels: { style: { colors: '#8b949e' } } },
    grid: { borderColor: '#1e2530' },
    legend: { show: false }
});
window.ppsBarChart.render();

// ── PPS Valides Ranking Chart (top 15 horizontal bar) ──
const ppsValideLabels = @json($ppsValideRankingTop15->pluck('nom'));
const ppsValideData = @json($ppsValideRankingTop15->pluck('valide')->map(fn($v) => (int)$v));
window.ppsValideChart = new ApexCharts(document.getElementById('ppsValideChart'), {
    chart: { type: 'bar', height: 440, foreColor: '#8b949e',
        toolbar: { show: false },
        animations: { enabled: false }
    },
    series: [{ name: 'Valides', data: ppsValideData }],
    colors: ['#00E676'],
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
    dataLabels: { enabled: true, style: { colors: ['#e0e0e0'], fontSize: '11px', fontFamily: 'Inter, sans-serif' } },
    xaxis: { labels: { style: { colors: '#8b949e', fontSize: '11px' } } },
    yaxis: { labels: { style: { colors: '#00FFFF', fontSize: '11px', fontFamily: 'Inter, sans-serif' },
             maxWidth: 300, trim: false } },
    grid: { borderColor: '#1e2530' },
    legend: { show: false }
});
window.ppsValideChart.render();

// ── Filter by status ──
function filterByStatus(status) {
    activeFilter = (activeFilter === status) ? null : status;
    applyTableFilter();
    updateChartHighlights();

    document.querySelectorAll('.stat-card').forEach(c => {
        c.classList.toggle('ring-2', c.dataset.status === activeFilter);
        c.classList.toggle('ring-cyan-400', c.dataset.status === activeFilter);
    });
    document.getElementById('clearFilterBtn').classList.toggle('hidden', !activeFilter);
}

function updateChartHighlights() {
    const cols = activeFilter
        ? chartColors.map((c, i) => statusKeys[i] === activeFilter ? chartColors[i] : chartColorsDim[i])
        : chartColors;
    window.ppsDonutChart.updateOptions({ colors: cols, dataLabels: { style: { colors: ['#fff'] } } }, false, false, false);
    window.ppsBarChart.updateOptions({ colors: cols, dataLabels: { style: { colors: ['#e0e0e0'] } } }, false, false, false);
}

function clearFilter() {
    activeFilter = null;
    document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('ring-2','ring-cyan-400'));
    document.getElementById('clearFilterBtn').classList.add('hidden');
    applyTableFilter();
    updateChartHighlights();
}

function applyTableFilter() {
    const q = document.getElementById('ppsSearch').value.toLowerCase();
    document.querySelectorAll('.pps-row').forEach(row => {
        const ppsName = row.querySelector('button').textContent.trim();
        const pps = ppsData.find(p => p.nom === ppsName);
        let visible = row.dataset.name.includes(q);
        if (visible && activeFilter && pps) {
            visible = pps[activeFilter] > 0;
        }
        row.style.display = visible ? '' : 'none';
    });
    // Also filter ranking table
    document.querySelectorAll('.ranking-row').forEach(row => {
        const ppsName = row.querySelector('span, button').textContent.trim();
        const pps = ppsData.find(p => p.nom === ppsName);
        let visible = true;
        if (activeFilter && pps) {
            visible = pps[activeFilter] > 0;
        }
        row.style.display = visible ? '' : 'none';
    });
}

// ── PPS Detail ──
function showPpsDetail(ppsName, highlightStatus) {
    const pps = ppsData.find(p => p.nom === ppsName);
    if (!pps) return;

    // Reset previous detail
    document.getElementById('detailEmployeeList').classList.add('hidden');
    document.getElementById('detailListContent').innerHTML = '';

    document.getElementById('detailTitle').textContent = 'PPS : ' + pps.nom;

    const statsContainer = document.getElementById('detailStats');
    statsContainer.innerHTML = statusKeys.map(s => {
        const count = pps[s];
        const isActive = s === highlightStatus;
        return `<button onclick="showEmployeeList('${pps.nom}','${s}')" class="card p-3 text-center ${isActive ? 'ring-2 ring-cyan-400' : ''}">
            <div class="stat-value" style="color:${colorMap[s]}">${count}</div>
            <div class="text-xs text-gray-400 mt-1">${labelMap[s]}</div>
        </button>`;
    }).join('');

    document.getElementById('ppsDetail').classList.remove('hidden');
    document.getElementById('ppsDetail').scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (highlightStatus) showEmployeeList(pps.nom, highlightStatus);
}

function showEmployeeList(ppsName, status) {
    const pps = ppsData.find(p => p.nom === ppsName);
    if (!pps) return;

    const emps = pps[status + '_emps'] || [];
    document.getElementById('detailListTitle').textContent = `${emps.length} employe(s) - ${labelMap[status]}`;
    const container = document.getElementById('detailListContent');
    if (emps.length === 0) {
        container.innerHTML = '<div class="text-gray-500 text-sm">Aucun employe</div>';
    } else {
        container.innerHTML = emps.map(e => `<div class="text-sm text-gray-300 py-1 px-2 bg-white/5 rounded">${e}</div>`).join('');
    }
    document.getElementById('detailEmployeeList').classList.remove('hidden');

    // Highlight active stat card
    document.querySelectorAll('#detailStats button').forEach((btn, i) => {
        btn.classList.toggle('ring-2', statusKeys[i] === status);
        btn.classList.toggle('ring-cyan-400', statusKeys[i] === status);
    });
}

function closeDetail() {
    document.getElementById('ppsDetail').classList.add('hidden');
    document.getElementById('detailEmployeeList').classList.add('hidden');
}

// ── PPS Detail in List tab ──
function showPpsDetailList(ppsName, highlightStatus) {
    const pps = ppsData.find(p => p.nom === ppsName);
    if (!pps) return;

    // Reset previous detail
    document.getElementById('detailEmployeeListList').classList.add('hidden');
    document.getElementById('detailListContentList').innerHTML = '';

    document.getElementById('detailListTitle2').textContent = 'PPS : ' + pps.nom;

    const container = document.getElementById('detailStatsList');
    container.innerHTML = statusKeys.map(s => {
        const count = pps[s];
        const isActive = s === highlightStatus;
        return `<button onclick="showEmployeeListList('${pps.nom}','${s}')" class="card p-3 text-center ${isActive ? 'ring-2 ring-cyan-400' : ''}">
            <div class="stat-value" style="color:${colorMap[s]}">${count}</div>
            <div class="text-xs text-gray-400 mt-1">${labelMap[s]}</div>
        </button>`;
    }).join('');

    document.getElementById('ppsDetailList').classList.remove('hidden');
    document.getElementById('ppsDetailList').scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (highlightStatus) showEmployeeListList(pps.nom, highlightStatus);
}

function showEmployeeListList(ppsName, status) {
    const pps = ppsData.find(p => p.nom === ppsName);
    if (!pps) return;

    const emps = pps[status + '_emps'] || [];
    document.getElementById('detailListTitle3').textContent = `${emps.length} employe(s) - ${labelMap[status]}`;
    const container = document.getElementById('detailListContentList');
    if (emps.length === 0) {
        container.innerHTML = '<div class="text-gray-500 text-sm">Aucun employe</div>';
    } else {
        container.innerHTML = emps.map(e => `<div class="text-sm text-gray-300 py-1 px-2 bg-white/5 rounded">${e}</div>`).join('');
    }
    document.getElementById('detailEmployeeListList').classList.remove('hidden');

    document.querySelectorAll('#detailStatsList button').forEach((btn, i) => {
        btn.classList.toggle('ring-2', statusKeys[i] === status);
        btn.classList.toggle('ring-cyan-400', statusKeys[i] === status);
    });
}

function closeDetailList() {
    document.getElementById('ppsDetailList').classList.add('hidden');
    document.getElementById('detailEmployeeListList').classList.add('hidden');
}

// ── Tab switching ──
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(tabId).classList.remove('hidden');
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-cyan-400', 'bg-cyan-400/10', 'font-semibold');
        btn.classList.add('text-gray-500');
        if (btn.dataset.tab === tabId) {
            btn.classList.add('text-cyan-400', 'bg-cyan-400/10', 'font-semibold');
            btn.classList.remove('text-gray-500');
        }
    });
    // Clear highlight when switching to Classement
    if (tabId === 'tabRanking') {
        clearFilter();
    }
    // Refresh charts when tab is shown (fix ApexCharts hidden container dimensions)
    if (tabId === 'tabCharts') {
        setTimeout(() => {
            window.ppsDonutChart.updateOptions({ chart: { width: '100%' } }, false, false, false);
            window.ppsBarChart.updateOptions({ chart: { width: '100%' } }, false, false, false);
        }, 50);
    }
    if (tabId === 'tabRanking') {
        setTimeout(() => {
            window.ppsValideChart.updateOptions({ chart: { width: '100%' } }, false, false, false);
        }, 50);
    }
}

// ── Search ──
document.getElementById('ppsSearch').addEventListener('input', applyTableFilter);
</script>
<style>
.stat-btn { background: transparent; border: 0; cursor: pointer; padding: 2px 6px; border-radius: 4px; font: inherit; }
.stat-btn:hover { background: rgba(255,255,255,0.08); }
.stat-card { transition: all 0.2s; }
.stat-card:hover { transform: translateY(-2px); }
</style>
@endpush
