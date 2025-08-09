<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    //store the feedback.
    public function storeFeedback(Request $request){
        $validator = Validator::make($request->all(), [
            'message' => 'required|min:3',
            'user_type' => 'required',
            'user_id' => 'required|exists:residents,id|waste_collectors,id',
            'is_public' => 'required',
            'rating' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        $feedback = new Feedback();
        $feedback->message = $request->message;
        $feedback->user_type = $request->user_type;
        $feedback->user_id = $request->user_id;
        $feedback->is_public = $request->is_public;
        $feedback->rating = $request->rating;

        $feedback->save();

        return response()->json([
            'status' => true,
            'message' => 'Feedback created successfully',
            'data' => $feedback
        ]);
    }

    //show a single waste invoice by ID
    public function showFeedback($id){
        $feedback = Feedback::find($id);

        if(!$feedback){
            return response()->json([
                'status' => false,
                'message' => 'Feedback not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $feedback
        ]);
    }

    // Route for showing all waste invoices
    public function showAllFeedback(){
        $feedback = Feedback::all();

        if($feedback->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No feedback found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $feedback
        ]);
    }

    // Route for updating a waste invoice by ID
    public function updateFeedback(Request $request, $id){
        $feedback = Feedback::find($id);

        if($feedback === null){
            return response()->json([
                'status' => false,
                'message' => 'feedback not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
           'message' => 'required|min:3',
            'user_type' => 'required',
            'user_id' => 'required',
            'is_public' => 'required',
            'rating' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        $feedback->message = $request->message;
        $feedback->user_type = $request->user_type;
        $feedback->user_id = $request->user_id;
        $feedback->is_public = $request->is_public;
        $feedback->rating = $request->rating;

        $feedback->save();

        return response()->json([
            'status' => true,
            'message' => 'Feedback updated successfully',
            'data' => $feedback
        ]);
    }
}
