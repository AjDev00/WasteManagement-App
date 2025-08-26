<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\WasteCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WasteCollectorController extends Controller
{
    // Store a newly created waste collector.
    public function storeWasteCollector(Request $request){
        $fields = $request->validate( [
            'firstname' => 'required|string|min:3',
            'lastname' => 'required|string|min:3',
            'email' => 'required|email|unique:waste_collectors,email',
            'phone_number' => 'required|min:10|max:15',
            'password' => 'required|string|min:8|confirmed',
            'verifying_id' => 'required|string|unique:waste_collectors,verifying_id',
            'verify_type' => 'required|string',
            'verifying_image' => 'required',
        ]);

        //hash the password
        $fields['password'] = Hash::make($fields['password']);

        $waste_collector = WasteCollector::create($fields);

        $token = $waste_collector->createToken($request->firstname);

        return response()->json([
            'status' => true,
            'message' => 'Waste collector registered successfully',
            'data' => $waste_collector,
            'token' => $token->plainTextToken
        ]);
    }

    //login a waste collector.
    public function loginWasteCollector(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $waste_collector = WasteCollector::where('email', $request->email)->first();

        if (!$waste_collector || !Hash::check($request->password, $waste_collector->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email or password',
            ]);
        }

        $token = $waste_collector->createToken($waste_collector->email);

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => $waste_collector,
            'token' => $token->plainTextToken
        ]);
    }

    //view all wastecollection available.
    public function showAllCollection(){
        $collection = Collection::with('wasteInvoices')->latest()->get();

        if($collection->isNotEmpty()){
            return response()->json([
                'status' => true,
                'data' => $collection
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No waste collection found!'
        ]);
    }

    // Show a single waste collector with the ID.
    public function showWasteCollector($id){
        $wasteCollector = WasteCollector::find($id);

        if(!$wasteCollector){
            return response()->json([
                'status' => false,
                'message' => 'Waste Collector do not exist!',
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $wasteCollector
        ]);
    }

    // Show all waste collectors.
    public function showAllWasteCollector(){
        $wasteCollector = WasteCollector::all();

        if($wasteCollector->isNotEmpty()){
            return response()->json([
                'status' => true,
                'data' => $wasteCollector
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No waste collector found!'
        ]);
    }

   public function updateWasteCollector(Request $request, $id){
    $wasteCollector = WasteCollector::find($id);

    if($wasteCollector === null){
        return response()->json([
            'status' => false,
            'message' => 'Waste collector not found'
        ]);
    }

    $validator = Validator::make($request->all(), [
       'firstname' => 'required|string|min:3',
        'lastname' => 'required|string|min:3',
        'email' => 'required|email|unique:waste_collectors,email',
        'phone_number' => 'required|min:10|max:15|unique:waste_collectors,phone_number',
        'password' => 'required|string|min:8',
    ]);

    if($validator->fails()){
        return response()->json([
            'status' => false,
            'message' => 'Please fix the following errors',
            'errors' => $validator->errors()
        ]);
    }

        $wasteCollector->firstname = $request->firstname;
        $wasteCollector->lastname = $request->lastname;
        $wasteCollector->email = $request->email;
        $wasteCollector->phone_number = $request->phone_number;
        if($request->filled('password')){
            $wasteCollector->password = bcrypt($request->password);
        }

        $wasteCollector->save();

        return response()->json([
            'status' => true,
            'message' => 'Updated Successfully',
            'data' => $wasteCollector
        ]);
   }

   public function logout(Request $request){
        //get all user with the token and delete all.
        $request->wasteCollector()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Log out successfull!'
        ]);
    }
}
