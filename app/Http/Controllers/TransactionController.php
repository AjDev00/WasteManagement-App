<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    public function getTransactions($resident_id){
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $todayTransaction = Transaction::where('resident_id', $resident_id)
                            ->whereDate('updated_at', $today)
                            ->latest()
                            ->get();

        $yesterdayTransaction = Transaction::where('resident_id', $resident_id)
                            ->whereDate('updated_at', $yesterday)
                            ->latest()
                            ->get();

        $thisWeekTransaction = Transaction::where('resident_id', $resident_id)
                            ->whereBetween('updated_at', [$weekStart, $weekEnd])
                            ->latest()
                            ->get();

        $transactions = Transaction::where('resident_id', $resident_id)
                        ->latest()
                        ->get()
                        ->groupBy(function($transaction) {
                            return \Carbon\Carbon::parse($transaction->created_at)->format('Y-m-d');
                        });

        return response()->json([
            'status' => true,
            'allData' => $transactions,
            'todayData' => $todayTransaction,
            'yesterdayData' => $yesterdayTransaction,
            'this_week' => $thisWeekTransaction,
        ]);
    }

    public function getWCTransactions($waste_collector_id){
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $todayTransaction = Transaction::where('waste_collector_id', $waste_collector_id)
                            ->whereDate('updated_at', $today)
                            ->latest()
                            ->get();

        $yesterdayTransaction = Transaction::where('waste_collector_id', $waste_collector_id)
                            ->whereDate('updated_at', $yesterday)
                            ->latest()
                            ->get();

        $thisWeekTransaction = Transaction::where('waste_collector_id', $waste_collector_id)
                            ->whereBetween('updated_at', [$weekStart, $weekEnd])
                            ->latest()
                            ->get();

        $transactions = Transaction::where('waste_collector_id', $waste_collector_id)
                        ->latest()
                        ->get()
                        ->groupBy(function($transaction) {
                            return \Carbon\Carbon::parse($transaction->created_at)->format('Y-m-d');
                        });

        return response()->json([
            'status' => true,
            'allData' => $transactions,
            'todayData' => $todayTransaction,
            'yesterdayData' => $yesterdayTransaction,
            'this_week' => $thisWeekTransaction,
        ]);
    }
}
