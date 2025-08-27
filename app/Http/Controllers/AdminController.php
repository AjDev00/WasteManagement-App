<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Resident;
use App\Models\WasteCollector;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    public function getAllResidentDetailsWithInvoicesAndLocation(){
        $collection = Collection::with(['wasteInvoices', 'location', 'resident', 'picker'])->get();

        if($collection === null){
            return response()->json([
                'status' => false,
                'message' => 'No residents'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $collection
        ]);
    }

    public function getSpecificResidentDetailsWithInvoicesAndLocation($residentName){
        // Find residents whose name contains the search string (case-insensitive)
        $residentIds = Resident::where('fullname', 'LIKE', '%' . $residentName . '%')
            ->pluck('id');

        if ($residentIds->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No resident found with a similar name'
            ]);
        }

        // Get all collections linked to those residents
        $collections = Collection::with(['wasteInvoices', 'location', 'resident', 'picker'])
            ->whereIn('resident_id', $residentIds)
            ->get();

        if ($collections->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'This resident has no waste records'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $collections,
        ]);
    }

    public function getSpecificWasteCollectorWithInvoicesHandled($pickerName){
        // Find picker by firstname or lastname
        $picker = WasteCollector::where('firstname', 'LIKE', '%' . $pickerName . '%')
            ->orWhere('lastname', 'LIKE', '%' . $pickerName . '%')
            ->first(); // get only one collector

        if (!$picker) {
            return response()->json([
                'status' => false,
                'message' => 'Waste collector not found'
            ]);
        }

        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        // Get all collections for that collector
        $collections = Collection::with('picker')
            ->where('waste_collector_id', $picker->id)
            ->get();

        // Calculate stats for this picker
        $todayCount = Collection::where('waste_collector_id', $picker->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->count();

        $weekCount = Collection::where('waste_collector_id', $picker->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->count();

        $totalCount = Collection::where('waste_collector_id', $picker->id)
            ->where('status', 'completed')
            ->count();

        // Attach stats to picker
        $picker->today = $todayCount;
        $picker->this_week = $weekCount;
        $picker->total_completed = $totalCount;

        if ($collections->isEmpty()) {
            return response()->json([
                'status' => false,
                'data' => [$picker],
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [$picker],   // info about the collector
            // 'collections' => $collections, // all their collections
        ]);
    }
    
    public function getAllWasteCollectorWithInvoicesHandles(){
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        // Fetch all collectors with their collections
        $collectors = WasteCollector::with('collection')->get();

        // Map collectors with stats
        $collectorsWithStats = $collectors->map(function ($collector) use ($today, $weekStart, $weekEnd) {
            $todayCount = Collection::where('waste_collector_id', $collector->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', $today)
                ->count();

            $weekCount = Collection::where('waste_collector_id', $collector->id)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$weekStart, $weekEnd])
                ->count();

            $totalCount = Collection::where('waste_collector_id', $collector->id)
                ->where('status', 'completed')
                ->count();

            // Attach stats to collector
            $collector->today = $todayCount;
            $collector->this_week = $weekCount;
            $collector->total_completed = $totalCount;

            return $collector;
        });

        return response()->json([
            'status' => true,
            'data' => $collectorsWithStats,
        ]);
    }

    //view all withdrawal requests.
    public function viewAllWithdrawalRequests(){
        $withReq = WithdrawalRequest::with(['resident', 'picker'])->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $withReq
        ]);
    }

    //update a single withdrawal request to approved.
    public function approvePayments(Request $request, $id){
        $data = $request->validate([
            'approved_by' => 'required|exists:supervisors,id',
        ]);

        $withdrawalRequest = WithdrawalRequest::find($id);

        if (!$withdrawalRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Withdrawal request not found.'
            ], 404);
        }

        $withdrawalRequest->update([
            'status' => 'approved',
            'approved_by' => $data['approved_by'],
        ]);

        //create notification if resident.
        if($withdrawalRequest->resident_id){
            Notification::create([
                'resident_id'        => $withdrawalRequest->resident_id,
                'waste_collector_id' => null,
                'title'              => 'Withdrawal Approved',
                'message'            => "Your withdrawal request has been approved and payment has been made to your bank account.",
                'message_type'       => 'withdrawal_approved',
            ]);
        }

        //create notification if picker.
        if($withdrawalRequest->waste_collector_id){
            Notification::create([
                'resident_id'        => null,
                'waste_collector_id' => $withdrawalRequest->waste_collector_id,
                'title'              => 'Withdrawal Approved',
                'message'            => "Your withdrawal request has been approved and payment has been made to your bank account.",
                'message_type'       => 'withdrawal_approved',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Withdrawal request approved successfully!',
            'data' => $withdrawalRequest, // ✅ return the updated model instead of []
        ]);
    }

}
