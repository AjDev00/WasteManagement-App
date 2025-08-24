<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Earning;
use App\Models\Notification;
use App\Models\WasteInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EarningController extends Controller
{
    public function acceptedWaste(Request $request)
    {
        // Validate incoming request
        $earnings = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'waste_collector_id' => 'required|exists:waste_collectors,id',
            'collection_id' => 'required|exists:collections,id',
            'earning' => 'required|numeric|min:0',
            'reference_no' => 'required|string'
        ]);

        DB::transaction(function () use ($earnings) {
            // Find earning record with this reference_no and collection
            $earningRecord = Earning::where('collection_id', $earnings['collection_id'])
                ->where('resident_id', $earnings['resident_id'])
                ->where('reference_no', $earnings['reference_no'])
                ->first();
    
            if (!$earningRecord) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid reference number provided.',
                ], 422);
            }
    
            // Update the earning record now that it's confirmed
            $previousEarning = Earning::where('resident_id', $earnings['resident_id'])
                ->sum('earning');
    
            $totalEarning = $previousEarning > 0 
                ? $previousEarning + $earnings['earning'] // use addition instead of multiply
                : $earnings['earning'];
    
            $earningRecord->update([
                'waste_collector_id' => $earnings['waste_collector_id'],
                'earning'            => $earnings['earning'],
                'total_earning'      => $totalEarning,
            ]);
    
            // Update the collection status and amount
            $collectionId = $earnings['collection_id'];
            $collection = Collection::find($collectionId);
            $collection->status = 'completed';
            $collection->amount_total = $earnings['earning'];
            $collection->save();
    
            // Update the waste invoice status
            WasteInvoice::where('collection_id', $collectionId)->update(['status' => 'paid']);
    
            // Create notification for household
            Notification::create([
                'resident_id'        => $collection->resident_id,
                'waste_collector_id' => null,
                'title'              => 'Payment Received',
                'message'            => "Your payment of {$totalEarning} has been received for the waste pickup. Check available earnings.",
                'message_type'       => 'payment_received',
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Earnings recorded successfully',
        ]);
    }

    public function getEarnings($resident_id)
    {
        $earnings = Earning::where('resident_id', $resident_id)->latest()->first();

        return response()->json([
            'status'  => true,
            'data'    => $earnings,
            // 'count'   => $earnings->count(),
        ]);
    }
}
