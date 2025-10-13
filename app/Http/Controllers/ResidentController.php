<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResidentController extends Controller
{
    //store a newly created resident.
    public function storeResident(Request $request){
        $fields = $request->validate( [
            'fullname' => 'required|string|min:3',
            'address' => 'required|string|min:5',
            'phone_number' => 'required|min:10|max:15|unique:residents,phone_number',
            'email' => 'required|email|unique:residents,email',
            'password' => 'required|string|min:8|confirmed' ,  
        ]);

        //hash the password
        $fields['password'] = Hash::make($fields['password']);

        $resident = Resident::create($fields);

        $token = $resident->createToken($request->fullname);

        return response()->json([
            'status' => true,
            'message' => 'Resident created successfully',
            'data' => $resident,
            'token' => $token->plainTextToken
        ]);
    }

    public function loginResident(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        //check if the resident exists
        $resident = Resident::where('email', $request->email)->first();

        if(!$resident || !Hash::check($request->password, $resident->password)){
            return response()->json([
                'status' => false,
                'message' => 'Email or password incorrect'
            ]);
        }

        $token = $resident->createToken($resident->fullname);

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => $resident,
            'token' => $token->plainTextToken
        ]);
    }

    public function forgotPassword(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        //check if the resident exists
        $resident = Resident::where('email', $request->email)->first();

        if(!$resident){

            return response()->json([
                'status' => 'false',
                'message' => 'Email incorrect'
            ]);
        } else if($resident && Hash::check($request->password, $resident->password)){

            return response()->json([
                'status' => false,
                'message' => 'Old password cant be the same as new password'
            ]);
        } else if($resident && !Hash::check($request->password, $resident->password)){

            $resident->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully'
            ]);
        } 

        // if(!$resident || !Hash::check($request->password, $resident->password)){
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Email or password incorrect'
        //     ]);
        // }

        // $getPassword = Resident::where('email', $email)->first('password');


    }

    public function changePassword(Request $request){
        $request->validate([
            'email' => 'required|email',
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8',
        ]);

        //check if the resident exists
        $resident = Resident::where('email', $request->email)->first();

        //email does not exist.
        if(!$resident){

            return response()->json([
                'status' => 'false',
                'message' => 'Email incorrect'
            ]);
        } 
        
        //email exists, but old password does not match previous.
        else if($resident && !Hash::check($request->old_password, $resident->password)){

            return response()->json([
                'status' => false,
                'message' => 'Old password incorrect'
            ]);
        }

        //email exists, but old password cannot be the same as new password.
        else if($resident && Hash::check($request->new_password, $resident->password)){

            return response()->json([
                'status' => false,
                'message' => 'Old password cant be the same as new password'
            ]);
        } 
        
        //email exists and the new password is not the same as the old password.
        else if($resident && !Hash::check($request->new_password, $resident->password)){

            $resident->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Password changed successfully'
            ]);
        } 

        // if(!$resident || !Hash::check($request->password, $resident->password)){
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Email or password incorrect'
        //     ]);
        // }

        // $getPassword = Resident::where('email', $email)->first('password');


    }

    //show a single resident by ID
    public function showResident($id){
        $resident = Resident::find($id);

        if(!$resident){
            return response()->json([
                'status' => false,
                'message' => 'Resident not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $resident
        ]);
    }

    // Route for showing all residents
    public function showAllResidents(){
        $residents = Resident::all();

        if($residents->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No residents found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $residents
        ]);
    }

    // Route for updating a resident by ID
    public function updateResident(Request $request, $id){
        $resident = Resident::find($id);

        if($resident === null){
            return response()->json([
                'status' => false,
                'message' => 'Resident not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|min:3',
            'phone_number' => 'required|min:10|max:15',
            'email' => 'required|email',
        ]);

        //error handling
        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        // Update the resident
        $resident->fullname = $request->fullname;
        $resident->phone_number = $request->phone_number;
        $resident->email = $request->email;
        if($request->filled('password')){
            $resident->password = bcrypt($request->password);
        }
        $resident->save();

        return response()->json([
            'status' => true,
            'message' => 'Resident updated successfully',
            'data' => $resident
        ]);
    }

    //logout users.
    public function logout(Request $request){
        //get all user with the token and delete all.
        $request->resident()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Log out successfull!'
        ]);
    }
}
