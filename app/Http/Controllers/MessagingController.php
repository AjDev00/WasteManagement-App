<?php

namespace App\Http\Controllers;

use App\Models\Messaging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessagingController extends Controller
{
    //store the feedback.
    public function storeMessage(Request $request){
        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|exists:residents,id',
            'waste_collector_id' => 'required|exists:waste_collectors,id',
            'message' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        $messages = new Messaging();
        $messages->resident_id = $request->resident_id;
        $messages->waste_collector_id = $request->waste_collector_id;
        $messages->message = $request->message;

        $messages->save();

        return response()->json([
            'status' => true,
            'message' => 'Message sent',
            'data' => $messages
        ]);
    }

    //show a single waste invoice by ID
    public function showMessage($id){
        $messages = Messaging::find($id);

        if(!$messages){
            return response()->json([
                'status' => false,
                'message' => 'messages$messages not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $messages
        ]);
    }

    // Route for showing all waste invoices
    public function showAllMessage(){
        $messages = Messaging::all();

        if($messages->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No messages found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $messages
        ]);
    }

    // Route for updating a waste invoice by ID
    public function updateMessage(Request $request, $id){
        $messages = Messaging::find($id);

        if($messages === null){
            return response()->json([
                'status' => false,
                'message' => 'messages not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|exists:residents,id',
            'waste_collector_id' => 'required|exists:waste_collectors,id',
            'message' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        $messages->resident_id = $request->resident_id;
        $messages->waste_collector_id = $request->waste_collector_id;
        $messages->message = $request->message;

        $messages->save();

        return response()->json([
            'status' => true,
            'message' => 'message updated successfully',
            'data' => $messages
        ]);
    }
}
