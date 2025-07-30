<?php

namespace App\Http\Controllers;

use App\Models\WasteCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WasteCollectorController extends Controller
{
    // Store a newly created waste collector.
    public function storeWasteCollector(Request $request){
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|min:3',
            'lastname' => 'required|string|min:3',
            'email' => 'required|email|unique:waste_collectors,email',
            'phone_number' => 'required|string|min:10|max:15',
            'password' => 'required|string|min:8',
            'verifying_id' => 'required|string',
            'verify_type' => 'required|string',
            'verifying_image' => 'required',
            'bank_name' => 'required|string',
            'bank_account_number' => 'required|string|min:10|max:15',
            'is_verified' => 'required|boolean',
            'verified_by' => 'string',
        ]);

        //error handling
        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        // Create the waste collector
        $wasteCollector = new WasteCollector();
        $wasteCollector->firstname = $request->firstname;
        $wasteCollector->lastname = $request->lastname;
        $wasteCollector->email = $request->email;
        $wasteCollector->phone_number = $request->phone_number;
        $wasteCollector->password = bcrypt($request->password);
        $wasteCollector->verifying_id = $request->verifying_id;
        $wasteCollector->verify_type = $request->verify_type;
        $wasteCollector->verifying_image = $request->verifying_image;
        $wasteCollector->bank_name = $request->bank_name;
        $wasteCollector->bank_account_number = $request->bank_account_number;
        $wasteCollector->is_verified = $request->is_verified;
        $wasteCollector->verified_by = $request->verified_by;

        $wasteCollector->save();

        return response()->json([
            'status' => true,
            'message' => 'Waste collector registered successfully',
            'data' => $wasteCollector
        ]);
    }
}
