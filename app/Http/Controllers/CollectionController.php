<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CollectionController extends Controller
{
    //store a newly created collection.
    public function storeCollection(Request $request){
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

        // Create the location
        $collection = new Collection();
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
            'message' => 'Supervisor created successfully',
            'data' => $collection
        ]);
    }

    //show a single collection by ID
    public function showCollection($id){
        $collection = Collection::find($id);

        if(!$collection){
            return response()->json([
                'status' => false,
                'message' => 'collection not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $collection
        ]);
    }

    // Route for showing all collections
    public function showAllCollection(){
        $collection = Collection::all();

        if($collection->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No collections found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $collection
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
