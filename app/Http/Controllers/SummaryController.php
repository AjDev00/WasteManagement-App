<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\WasteInvoice;

class SummaryController extends Controller
{
    // GET /api/summary/{residentId}?days=7
    public function weeklySummary(Request $request, $residentId)
    {
        $days = intval($request->query('days', 7));
        $end = Carbon::now()->endOfDay();
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        // 1) daily counts for the date range
        $daily = WasteInvoice::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_by', $residentId) // or resident_id depending on schema
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->pluck('count', 'date') // map date => count
                ->toArray();

        // build full list of days (labels) and fill zeros where missing
        $labels = [];
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $start->copy()->addDays($i);
            $label = $d->format('D'); // Mon, Tue...
            $key = $d->toDateString(); // YYYY-MM-DD
            $labels[] = $label;
            $values[] = isset($daily[$key]) ? (int)$daily[$key] : 0;
        }

        // 2) breakdown by type (across same date range or entire user history - change as needed)
        $types = WasteInvoice::select('type_id', DB::raw('COUNT(*) as count'))
                    ->where('created_by', $residentId)
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('type_id')
                    ->get();

        // map types to a consumer-friendly array (adjust labels as your types table)
        $typeMap = [
            1 => 'Plastic',
            2 => 'E-Waste',
            3 => 'Cans',
            4 => 'Organic',
        ];
        $typesArr = $types->map(function($t) use ($typeMap) {
            return [
                'id' => (int)$t->type_id,
                'label' => $typeMap[$t->type_id] ?? 'Unknown',
                'count' => (int)$t->count,
            ];
        })->values();

        // total
        $total = array_sum($values);

        return response()->json([
            'chart' => $values,
            'days' => $labels,
            'total' => $total,
            'types' => $typesArr,
        ]);
    }
}
