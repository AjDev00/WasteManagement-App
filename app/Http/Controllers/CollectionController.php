<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Type;
use App\Models\WasteInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CollectionController extends Controller
{
    //store a newly created collection with waste invoice.
    public function storeCollection(Request $request){
        //validate the collection data.
        $collectionData = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'location_id' => 'required|exists:locations,id',
            'accepted_by' => 'required|exists:residents,id',
            'invoices' => 'required|array|min:1',
            'invoices.*.type_id' => 'required|exists:types,id',
            'invoices.*.kg' => 'required|numeric|min:0.1',
            'invoices.*.description' => 'nullable|string',
            'invoices.*.created_by' => 'required|exists:residents,id',
        ]);

        return DB::transaction(function () use($collectionData) {
            //insert the collection data.
            $collection = Collection::create([
                'resident_id' => $collectionData['resident_id'],
                'location_id' => $collectionData['location_id'],
                'accepted_by' => $collectionData['accepted_by'],
                'amount_total' => 0
            ]);

            $totalAmount = 0;

            //loop through each collectionData invoices as invoiceData - allows dropping of multiple invoices in the DB.
            foreach($collectionData['invoices'] as $invoiceData){
                $type = Type::find($invoiceData['type_id']); //find the type of waste with the invoice data type_id
                $amount = $invoiceData['kg'] * $type->min_price_per_kg;


                $wasteInvoice = WasteInvoice::create([
                    'collection_id' => $collection->id,
                    'type_id' => $invoiceData['type_id'],
                    'kg' => $invoiceData['kg'],
                    'created_by' => $invoiceData['created_by'],
                    'description' => $invoiceData['description'],
                    'status' => 'pending',
                    'amount' => $amount
                ]);
                
                $totalAmount += $amount;
            }

            //update the amount total column in the collection table.
            $collection->update(['amount_total' => $totalAmount]);

            return response()->json([
                'status' => true,
                'message' => 'Successfully',
                'collection' => $collection->fresh(),
                'waste_invoice' => $wasteInvoice
            ]);
        });
    }

    //update waste_collector_id in the collections table.
    public function attachPicker(Request $request, Collection $collection){
        $request->validate([
            'waste_collector_id' => 'required|exists:waste_collectors,id'
        ]);

        $collection->update([
            'waste_collector_id' => $request->waste_collector_id,
            'status' => 'picker assigned'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Picker assigned successfully',
            'data' => $collection->fresh()
        ]);
    }


    // Route for updating a collection by ID
    public function updateCollection(Request $request, $id){
        $collection = Collection::find($id);

        if($collection === null){
            return response()->json([
                'status' => false,
                'message' => 'collection not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
           'resident_id' => 'required|exists:residents,id',
            'waste_collector_id' => 'required|exists:waste_collectors,id',
            'amount' => 'required',
            'location_id' => 'required|exists:locations,id',
            'pickup_on' => 'required',
            'delivered_on' => 'required',
            'accepted_by' => 'required',
            'picture' => 'required',
            'summary' => 'required',
        ]);

        //error handling
        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        // Update the location
        $collection->resident_id = $request->resident_id;
        $collection->waste_collector_id = $request->waste_collector_id;
        $collection->amount = $request->amount;
        $collection->location_id = $request->location_id;
        $collection->pickup_on = $request->pickup_on;
        $collection->delivered_on = $request->delivered_on;
        $collection->accepted_by = $request->accepted_by;
        $collection->picture = $request->picture;
        $collection->summary = $request->summary;
        $collection->save();

        return response()->json([
            'status' => true,
            'message' => 'Supervisor updated successfully',
            'data' => $collection
        ]);
    }
}
