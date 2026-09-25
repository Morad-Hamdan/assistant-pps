@extends('layouts.app')
@section('title', $emp['name'])
@section('content')
<div class="max-w-4xl mx-auto">
    <a href="/employees" class="text-gray-400 hover:text-cyan-400 text-sm mb-4 inline-block">&larr; Retour</a>

    <div class="card p-4 md:p-6 mb-6">
        <h2 class="text-xl md:text-2xl font-bold">{{ $emp['name'] }}</h2>
        <div class="text-gray-400 text-sm mt-1">Matricule: <span class="font-mono text-white">{{ $emp['matricule'] }}</span></div>
        <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-400">
            <span>Processus: <span class="text-white">{{ $emp['processus'] ?? '-' }}</span></span>
            <span>UAP: <span class="text-white">{{ $emp['uap'] ?? '-' }}</span></span>
        </div>
        <div class="mt-3">
            @if($emp['suspended'] > 0)
                <span class="badge" style="background:#ff004040;color:#ff6b6b;">&#9888; ALERTE SUSPENSION</span>
            @elseif($emp['requalify'] > 0 || $emp['prevu'] > 0)
                <span class="badge" style="background:#ffa50030;color:#ffa500;">SURVEILLANCE</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 mb-6">
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-green-400">{{ $emp['valid'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Valides [OK]</div>
        </div>
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-yellow-400">{{ $emp['requalify'] }}</div>
            <div class="text-xs text-gray-400 mt-1">A requalifier [!]</div>
        </div>
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-purple-400">{{ $emp['prevu'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Prevus [~]</div>
        </div>
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-cyan-400">{{ $emp['en_cours'] }}</div>
            <div class="text-xs text-gray-400 mt-1">En cours [>]</div>
        </div>
        <div class="card p-3 md:p-4 text-center">
            <div class="stat-value text-red-400">{{ $emp['suspended'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Suspendus [X]</div>
        </div>
    </div>

    @if(count($emp['suspended_pps'] ?? []) > 0)
    <div class="card p-4 md:p-5 mb-4">
        <h3 class="text-sm font-semibold text-red-400 mb-3">PPS Suspendus [X]</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-800 text-xs">
                        <th class="text-left py-2 px-2">PPS</th>
                        <th class="text-left py-2 px-2">Date de Validation</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sDates = collect($emp['suspended_pps_dates'] ?? []); @endphp
                    @foreach($sDates as $d)
                    <tr class="border-b border-gray-800/30">
                        <td class="py-2 px-2 text-red-400 font-mono text-xs">{{ $d['pps'] }}</td>
                        <td class="py-2 px-2 text-gray-400 text-xs">{{ $d['date'] ? date('d/m/Y', strtotime($d['date'])) : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(count($emp['requalify_pps'] ?? []) > 0)
    <div class="card p-4 md:p-5 mb-4">
        <h3 class="text-sm font-semibold text-yellow-400 mb-3">A requalifier [!]</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($emp['requalify_pps'] as $pps)
                <span class="px-3 py-1 bg-yellow-400/10 border border-yellow-400/30 rounded-full text-xs text-yellow-400">{{ $pps }}</span>
            @endforeach
        </div>
    </div>
    @endif

    @if(count($emp['prevu_pps'] ?? []) > 0)
    <div class="card p-4 md:p-5 mb-4">
        <h3 class="text-sm font-semibold text-purple-400 mb-3">Prevus [~]</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-800 text-xs">
                        <th class="text-left py-2 px-2">PPS</th>
                        <th class="text-left py-2 px-2">Date de Validation</th>
                    </tr>
                </thead>
                <tbody>
                    @php $pDates = collect($emp['prevu_pps_dates'] ?? []); @endphp
                    @foreach($pDates as $d)
                    <tr class="border-b border-gray-800/30">
                        <td class="py-2 px-2 text-purple-400 font-mono text-xs">{{ $d['pps'] }}</td>
                        <td class="py-2 px-2 text-gray-400 text-xs">{{ $d['date'] ? date('d/m/Y', strtotime($d['date'])) : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(count($emp['en_cours_pps'] ?? []) > 0)
    <div class="card p-4 md:p-5 mb-4">
        <h3 class="text-sm font-semibold text-cyan-400 mb-3">En cours [>]</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($emp['en_cours_pps'] as $pps)
                <span class="px-3 py-1 bg-cyan-400/10 border border-cyan-400/30 rounded-full text-xs text-cyan-400">{{ $pps }}</span>
            @endforeach
        </div>
    </div>
    @endif

    @if(count($emp['valid_pps'] ?? []) > 0)
    <div class="card p-4 md:p-5">
        <h3 class="text-sm font-semibold text-green-400 mb-3">PPS Valides [OK]</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($emp['valid_pps'] as $pps)
                <span class="px-3 py-1 bg-green-400/10 border border-green-400/30 rounded-full text-xs text-green-400">{{ $pps }}</span>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
