<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\RecyclerCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RecyclerCompanyController extends Controller
{
    // Store a newly created waste collector.
    public function storeRecyclerCompany(Request $request){
        $fields = $request->validate( [
            'firstname' => 'required|string|min:3',
            'lastname' => 'required|string|min:3',
            'address' => 'required|min:8',
            'email' => 'required|email|unique:recycler_companies,email',
            'phone_number' => 'required|min:10|max:15',
            'password' => 'required|string|min:8|confirmed',
            'verifying_id' => 'required|string|unique:recycler_companies,verifying_id',
            'verify_type' => 'required|string',
            'verifying_image' => 'required',
        ]);

        //hash the password
        $fields['password'] = Hash::make($fields['password']);

        $recycler_company = RecyclerCompany::create($fields);

        $token = $recycler_company->createToken($request->firstname);

        return response()->json([
            'status' => true,
            'message' => 'Waste collector registered successfully',
            'data' => $recycler_company,
            'token' => $token->plainTextToken
        ]);
    }

    //login a waste collector.
    public function loginRecyclerCompany(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $recycler_company = RecyclerCompany::where('email', $request->email)->first();

        if (!$recycler_company || !Hash::check($request->password, $recycler_company->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email or password',
            ]);
        }

        $token = $recycler_company->createToken($recycler_company->email);

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => $recycler_company,
            'token' => $token->plainTextToken
        ]);
    }

    // Show all waste collectors.
    public function showAllRecyclerCompany(){
        $RecyclerCompany = RecyclerCompany::all();

        if($RecyclerCompany->isNotEmpty()){
            return response()->json([
                'status' => true,
                'data' => $RecyclerCompany
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No waste collector found!'
        ]);
    }

    //filter by name.
    public function filterCompanyByName($name){
        $ty = RecyclerCompany::where('firstname', $name)
            ->orWhere('lastname', $name)
            ->get();

        return response()->json([
            'data' => $ty
        ]);

    }

    public function updateRecyclerCompany(Request $request, $id){
        $recyclerCompany = RecyclerCompany::find($id);

        if($recyclerCompany === null){
            return response()->json([
                'status' => false,
                'message' => 'Recycler not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|min:3',
            'lastname' => 'required|string|min:3',
            'address'  => 'required|string|min:7',
            'email' => 'required|email|unique:recycler_companies,email',
            'phone_number' => 'required|min:10|max:15|unique:recycler_companies,phone_number',
            'password' => 'required|string|min:8',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

            $recyclerCompany->firstname = $request->firstname;
            $recyclerCompany->lastname = $request->lastname;
            $recyclerCompany->email = $request->email;
            $recyclerCompany->phone_number = $request->phone_number;
            if($request->filled('password')){
                $recyclerCompany->password = bcrypt($request->password);
            }

            $recyclerCompany->save();

            return response()->json([
                'status' => true,
                'message' => 'Updated Successfully',
                'data' => $recyclerCompany
            ]);
    }

    public function logout(Request $request){
        //get all user with the token and delete all.
        $request->recycler()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Log out successfull!'
        ]);
    }
}
