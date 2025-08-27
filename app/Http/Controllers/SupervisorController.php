<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SupervisorController extends Controller
{
    //store a newly created supervisor.
    public function storeSupervisor(Request $request){
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|min:3',
            'email' => 'required|email|unique:supervisors,email',
            'phone_number' => 'required|min:10|max:15',
            'password' => 'required|string|min:8',
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
        $supervisor = new Supervisor();
        $supervisor->fullname = $request->fullname;
        $supervisor->email = $request->email;
        $supervisor->phone_number = $request->phone_number;
        $supervisor->password = bcrypt($request->password);
        $supervisor->save();

        return response()->json([
            'status' => true,
            'message' => 'Supervisor created successfully',
            'data' => $supervisor
        ]);
    }

    //login
    public function loginSupervisor(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        //check if the supervisor exists
        $supervisor = Supervisor::where('email', $request->email)->first();

        if(!$supervisor || !Hash::check($request->password, $supervisor->password)){
            return response()->json([
                'status' => false,
                'message' => 'Email or password incorrect'
            ]);
        }

        $token = $supervisor->createToken($supervisor->fullname);

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => $supervisor,
            'token' => $token->plainTextToken
        ]);
    }

    //logout.
    public function logout(Request $request){
        //get all user with the token and delete all.
        $request->supervisor()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Log out successfull!'
        ]);
    }

    //show a single supervisor by ID
    public function showSupervisor($id){
        $supervisor = Supervisor::find($id);

        if(!$supervisor){
            return response()->json([
                'status' => false,
                'message' => 'supervisor not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $supervisor
        ]);
    }

    // Route for showing all supervisors
    public function showAllSupervisor(){
        $supervisors = Supervisor::all();

        if($supervisors->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No supervisors found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $supervisors
        ]);
    }

    // Route for updating a supervisor by ID
    public function updateSupervisor(Request $request, $id){
        $supervisor = Supervisor::find($id);

        if($supervisor === null){
            return response()->json([
                'status' => false,
                'message' => 'supervisor not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
           'fullname' => 'required|string|min:3',
            'email' => 'required|email|unique:supervisors,email',
            'phone_number' => 'required|min:10|max:15',
            'password' => 'required|string|min:8',
            'location_id' => 'required|exists:locations,id',
            'created_by' => 'required|string'
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
        $supervisor->fullname = $request->fullname;
        $supervisor->email = $request->email;
        $supervisor->phone_number = $request->phone_number;
        if($request->filled('password')){
            $supervisor->password = bcrypt($request->password);
        }
        $supervisor->location_id = $request->location_id;
        $supervisor->created_by = $request->created_by;
        $supervisor->save();

        return response()->json([
            'status' => true,
            'message' => 'Supervisor updated successfully',
            'data' => $supervisor
        ]);
    }
}
