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

    try {
        DB::transaction(function () use ($earnings) {
            // Find earning record with this reference_no and collection
            $earningRecord = Earning::where('collection_id', $earnings['collection_id'])
                ->where('resident_id', $earnings['resident_id'])
                ->where('reference_no', $earnings['reference_no'])
                ->first();

            if (!$earningRecord) {
                throw new \Exception('Invalid reference number provided.');
            }

            // Update the earning record now that it's confirmed
            $previousEarning = Earning::where('resident_id', $earnings['resident_id'])
                ->sum('earning');

            $totalEarning = $previousEarning > 0 
                ? $previousEarning + $earnings['earning'] 
                : $earnings['earning'];

            $earningRecord->update([
                'waste_collector_id' => $earnings['waste_collector_id'],
                'earning'            => $earnings['earning'],
                'total_earning'      => $totalEarning,
            ]);

            // Update the collection status and amount
            $collectionId = $earnings['collection_id'];
            $collection = Collection::find($collectionId);
            if (!$collection) {
                throw new \Exception('Collection not found.');
            }
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
                'message'            => "Your payment of {$earnings['earning']} WSC has been received for the waste pickup. Check available earnings.",
                'message_type'       => 'payment_received',
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Earnings recorded successfully, and payment processed.',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}


    public function getEarnings($resident_id)
    {
        $earnings = Earning::where('resident_id', $resident_id)->latest()->first();

        if($earnings === null){
            return response()->json([
            'status'  => false,
            'data'    => 0.00,
            // 'count'   => $earnings->count(),
        ]);
        }
        return response()->json([
            'status'  => true,
            'data'    => $earnings,
            // 'count'   => $earnings->count(),
        ]);
    }
}
