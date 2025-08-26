<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Location;
use App\Models\Resident;
use App\Models\WasteCollector;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function getAllResidentDetailsWithInvoicesAndLocation(){
        $collection = Collection::with(['wasteInvoices', 'location', 'resident'])->get();

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
        $residentId = Resident::where('fullname', $residentName)
                    ->pluck('id');

        $collection = Collection::with('wasteInvoices')
                    ->where('resident_id', $residentId)
                    ->get();
        $residentLocation = Location::where('resident_id', $residentId)->first();
        $resident = Resident::find($residentId);

        if($collection->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'This resident has no waste records'
            ]);
        }

        return response()->json([
            'status' => true,
            'collection' => $collection,
            'residentLocation' => $residentLocation,
            'resident' => $resident
        ]);
    }

    public function getSpecificWasteCollectorWithInvoicesHandled($pickerName){
        $pickerId = WasteCollector::where('firstname', $pickerName)
                    ->orWhere('lastname', $pickerName)
                    ->pluck('id');

        $collection = Collection::with('wasteInvoices')
                    ->where('waste_collector_id', $pickerId)
                    ->get();
        $picker = WasteCollector::find($pickerId);

        if($collection === null){
            return response()->json([
                'status' => false,
                'message' => 'No waste picked'
            ]);
        }

        return response()->json([
            'status' => true,
            'collection' => $collection,
            'picker' => $picker
        ]);
    }

    public function getAllWasteCollectorWithInvoicesHandles(){
        $collection = Collection::with(['wasteInvoices', 'resident', 'picker'])->get();

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
}
