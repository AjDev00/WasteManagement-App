<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TypeController extends Controller
{
    //Store a type.
    public function storeType(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:3',
            'is_active' => 'required|boolean',
            'min_kg' => 'required',
            'min_price_per_kg' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        $type = new Type();
        $type->title = $request->title;
        $type->is_active = $request->is_active;
        $type->min_kg = $request->min_kg;
        $type->min_price_per_kg = $request->min_price_per_kg;

        $type->save();

        return response()->json([
            'status' => true,
            'message' => 'Type Created!',
            'data' => $type
        ]);
    }

    // Show a single type with the ID.
    public function showType($id){
        $type = Type::find($id);

        if(!$type){
            return response()->json([
                'status' => false,
                'message' => 'type do not exist!',
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $type
        ]);
    }


    // Show all types.
    public function showAllType(){
        $type = Type::all();

        if($type->isNotEmpty()){
            return response()->json([
                'status' => true,
                'data' => $type
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No type found!'
        ]);
    }

    // Update a type.
   public function updateType(Request $request, $id){
    $type = Type::find($id);

    if($type === null){
        return response()->json([
            'status' => false,
            'message' => 'type not found'
        ]);
    }

    $validator = Validator::make($request->all(), [
       'title' => 'required|min:3',
       'is_active' => 'required|boolean',
        'min_kg' => 'required',
        'min_price_per_kg' => 'required'
    ]);

    if($validator->fails()){
        return response()->json([
            'status' => false,
            'message' => 'Please fix the following errors',
            'errors' => $validator->errors()
        ]);
    }

        $type->title = $request->title;
        $type->is_active = $request->is_active;
        $type->min_kg = $request->min_kg;
        $type->min_price_per_kg = $request->min_price_per_kg;

        $type->save();
        return response()->json([
            'status' => true,
            'message' => 'Updated Successfully',
            'data' => $type
        ]);
   }
}
