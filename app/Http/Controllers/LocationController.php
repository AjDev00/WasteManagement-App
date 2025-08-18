<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
     //store a newly created location.
    public function storeLocation(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'resident_id' => 'nullable|exists:residents,id',
            'waste_collector_id' => 'nullable|exists:waste_collectors,id',
            'country' => 'required',   
            'state' => 'required',
            'city' => 'required',
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
        $location = new Location();
        $location->title = $request->title;
        $location->resident_id = $request->resident_id;
        $location->waste_collector_id = $request->waste_collector_id;
        $location->country = $request->country;
        $location->state = $request->state;
        $location->city = $request->city;
        $location->save();

        return response()->json([
            'status' => true,
            'message' => 'location created successfully',
            'data' => $location
        ]);
    }

    public function checkIfUserLocationExists($resident_id){
        $location = Location::where('resident_id', $resident_id)->first();

        if($location){
            return response()->json([
                'status' => true,
                'data' => $location
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Location not found'
        ]);
    }

    //show a single location by ID
    public function showLocation($id){
        $location = Location::find($id);

        if(!$location){
            return response()->json([
                'status' => false,
                'message' => 'location not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $location
        ]);
    }

    // Route for showing all locations
    public function showAllLocation(){
        $locations = Location::all();

        if($locations->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No locations found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $locations
        ]);
    }

    // Route for updating a location by ID
    public function updateLocation(Request $request, $id){
        $location = Location::find($id);

        if($location === null){
            return response()->json([
                'status' => false,
                'message' => 'location not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
           'title' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',  
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
        $location->title = $request->title;
        $location->city = $request->city;
        $location->state = $request->state;
        $location->country = $request->country;
        $location->save();

        return response()->json([
            'status' => true,
            'message' => 'location updated successfully',
            'data' => $location
        ]);
    }
}
