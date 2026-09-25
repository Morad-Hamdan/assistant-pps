<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class NexusController extends Controller
{
    protected $api;

    public function __construct()
    {
        $this->api = env('FASTAPI_URL', 'http://localhost:8000');
    }

    public function dashboard()
    {
        $meta = Http::get("$this->api/api/employee/meta")->json();
        $analyze = Http::get("$this->api/api/employee/analyze")->json();
        $data = $analyze['data'] ?? [];

        $totals = ['valides' => 0, 'requalifier' => 0, 'prevu' => 0, 'en_cours' => 0, 'suspendus' => 0];
        $risk = ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];
        foreach ($data as $e) {
            $totals['valides'] += $e['valid'];
            $totals['requalifier'] += $e['requalify'];
            $totals['prevu'] += $e['prevu'];
            $totals['en_cours'] += $e['en_cours'];
            $totals['suspendus'] += $e['suspended'];
            $risk[$e['risk']] = ($risk[$e['risk']] ?? 0) + 1;
        }

        return view('pages.dashboard', compact('meta', 'data', 'totals', 'risk'));
    }

    public function employees()
    {
        $analyze = Http::get("$this->api/api/employee/analyze")->json();
        $employees = $analyze['data'] ?? [];

        $nonQualifies = collect($employees)->filter(fn($e) =>
            ($e['valid'] ?? 0) == 0 &&
            ($e['en_cours'] ?? 0) == 0 &&
            ($e['requalify'] ?? 0) == 0 &&
            ($e['suspended'] ?? 0) == 0 &&
            ($e['prevu'] ?? 0) == 0
        )->values();

        return view('pages.employees', compact('employees', 'nonQualifies'));
    }

    public function employeeDetail($matricule)
    {
        $analyze = Http::get("$this->api/api/employee/analyze")->json();
        $emp = collect($analyze['data'] ?? [])->firstWhere('matricule', $matricule);
        if (!$emp) abort(404);
        return view('pages.employee', compact('emp'));
    }

    public function pps()
    {
        $analyze = Http::get("$this->api/api/employee/analyze")->json();
        $data = $analyze['data'] ?? [];

        // Aggregate PPS stats + employee lists per status
        $ppsAgg = [];
        $statusMap = [
            'valid_pps' => 'valide', 'requalify_pps' => 'requalifier',
            'prevu_pps' => 'prevu', 'en_cours_pps' => 'en_cours', 'suspended_pps' => 'suspendu'
        ];
        foreach ($data as $emp) {
            $name = $emp['name'] . ' (' . $emp['matricule'] . ')';
            foreach ($statusMap as $field => $label) {
                foreach ($emp[$field] ?? [] as $p) {
                    $ppsAgg[$p]['nom'] = $p;
                    $ppsAgg[$p][$label] = ($ppsAgg[$p][$label] ?? 0) + 1;
                    $ppsAgg[$p][$label . '_emps'][] = $name;
                }
            }
        }
        ksort($ppsAgg);
        $ppsList = array_values($ppsAgg);

        // Global totals for charts
        $totals = ['valides' => 0, 'requalifier' => 0, 'prevu' => 0, 'en_cours' => 0, 'suspendus' => 0];
        foreach ($data as $e) {
            $totals['valides'] += $e['valid'];
            $totals['requalifier'] += $e['requalify'];
            $totals['prevu'] += $e['prevu'];
            $totals['en_cours'] += $e['en_cours'];
            $totals['suspendus'] += $e['suspended'];
        }

        // PPS sorted by valide desc for chart
        $ppsValideRanking = collect($ppsList)->sortByDesc(fn($p) => $p['valide'] ?? 0)->values();

        $ppsValideRankingTop15 = $ppsValideRanking->take(15);

        return view('pages.pps', compact('ppsList', 'totals', 'ppsValideRanking', 'ppsValideRankingTop15'));
    }
}
