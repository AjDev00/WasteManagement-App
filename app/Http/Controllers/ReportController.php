<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WasteInvoice;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function completedStats()
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $todayCount = WasteInvoice::where('status', 'delivered')
            ->whereDate('delivered_on', $today)
            ->count();

        $weekCount = WasteInvoice::where('status', 'delivered')
            ->whereBetween('delivered_on', [$weekStart, $weekEnd])
            ->count();

        $totalCount = WasteInvoice::where('status', 'delivered')->count();

        return response()->json([
            'today' => $todayCount,
            'this_week' => $weekCount,
            'total_completed' => $totalCount,
        ]);
    }

    public function completedBetween(Request $request)
    {
        $startDate = $request->query('start');
        $endDate = $request->query('end');

        $data = WasteInvoice::selectRaw('DATE(delivered_on) as date, COUNT(*) as total')
            ->where('status', 'delivered')
            ->whereBetween('delivered_on', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    public function dailyCompletedThisWeek()
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $data = WasteInvoice::selectRaw('DATE(delivered_on) as date, COUNT(*) as total')
            ->where('status', 'delivered')
            ->whereBetween('delivered_on', [$weekStart, $weekEnd])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }
}
