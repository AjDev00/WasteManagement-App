<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\DepositWaste;
use Illuminate\Http\Request;
use App\Models\WasteInvoice;
use Carbon\Carbon;

class ReportController extends Controller
{
    //report controller for PICKERS.
    public function completedStats($waste_collector_id)
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $todayCount = Collection::where('waste_collector_id', $waste_collector_id)
            ->where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->count();

        $todayCollection = Collection::with(['wasteInvoices', 'location'])
            ->where('waste_collector_id', $waste_collector_id)
            ->where('status', 'completed')
            ->whereDate('updated_at', $today)
            ->get();

        $weekCount = Collection::where('waste_collector_id', $waste_collector_id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->count();

        $weekCollection = Collection::with(['wasteInvoices', 'location'])
            ->where('waste_collector_id', $waste_collector_id)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$weekStart, $weekEnd])
            ->get();
        
        $totalCount = Collection::where('waste_collector_id', $waste_collector_id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'today' => $todayCount,
            'today_collection' => $todayCollection,
            'this_week' => $weekCount,
            'this_week_collection' => $weekCollection,
            'total_completed' => $totalCount,
        ]);
    }

    //report controller for RECYCLERS.
    public function completedRecyclerStats($recycler_company_id)
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $todayCount = DepositWaste::where('recycler_company_id', $recycler_company_id)
            ->whereDate('created_at', $today)
            ->count();

        $weekCount = DepositWaste::where('recycler_company_id', $recycler_company_id)
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->count();
        
        $totalCount = DepositWaste::where('recycler_company_id', $recycler_company_id)
            ->get()
            ->count();

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

        $count = WasteInvoice::where('status', 'delivered')
            ->whereBetween('delivered_on', [$startDate, $endDate])
            ->count();

        return response()->json([
            'completed' => $count,
            'from' => $startDate,
            'to' => $endDate,
        ]);
    }
}
