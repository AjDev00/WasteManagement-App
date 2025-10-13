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

    public function forgotPasswordWC(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        //check if the waste_collector exists
        $waste_collector = WasteCollector::where('email', $request->email)->first();

        if(!$waste_collector){

            return response()->json([
                'status' => 'false',
                'message' => 'Email incorrect'
            ]);
        } else if($waste_collector && Hash::check($request->password, $waste_collector->password)){

            return response()->json([
                'status' => false,
                'message' => 'Old password cant be the same as new password'
            ]);
        } else if($waste_collector && !Hash::check($request->password, $waste_collector->password)){

            $waste_collector->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully'
            ]);
        } 

        // if(!$waste_collector || !Hash::check($request->password, $waste_collector->password)){
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Email or password incorrect'
        //     ]);
        // }

        // $getPassword = waste_collector::where('email', $email)->first('password');


    }

    public function changePasswordWC(Request $request){
        $request->validate([
            'email' => 'required|email',
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8',
        ]);

        //check if the waste_collector exists
        $waste_collector = WasteCollector::where('email', $request->email)->first();

        //email does not exist.
        if(!$waste_collector){

            return response()->json([
                'status' => 'false',
                'message' => 'Email incorrect'
            ]);
        } 
        
        //email exists, but old password does not match previous.
        else if($waste_collector && !Hash::check($request->old_password, $waste_collector->password)){

            return response()->json([
                'status' => false,
                'message' => 'Old password incorrect'
            ]);
        }

        //email exists, but old password cannot be the same as new password.
        else if($waste_collector && Hash::check($request->new_password, $waste_collector->password)){

            return response()->json([
                'status' => false,
                'message' => 'Old password cant be the same as new password'
            ]);
        } 
        
        //email exists and the new password is not the same as the old password.
        else if($waste_collector && !Hash::check($request->new_password, $waste_collector->password)){

            $waste_collector->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Password changed successfully'
            ]);
        } 

        // if(!$waste_collector || !Hash::check($request->password, $waste_collector->password)){
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Email or password incorrect'
        //     ]);
        // }

        // $getPassword = waste_collector::where('email', $email)->first('password');


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
        'email' => 'required|email',
        'phone_number' => 'required|min:10|max:15',
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
