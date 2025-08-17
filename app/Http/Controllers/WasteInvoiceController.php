<?php

namespace App\Http\Controllers;

use App\Models\WasteInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WasteInvoiceController extends Controller
{
    //store a newly created waste invoice.
    public function storeWasteInvoice(Request $request){
        $validator = Validator::make($request->all(), [
            'collection_id' => 'required|exists:collections,id',
            'type_id' => 'required|exists:types,id',
            'kg' => 'required',
            'amount' => 'required',
            'picture' => 'required',
            'count' => 'required|integer'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        $waste_invoice = new WasteInvoice();
        $waste_invoice->collection_id = $request->collection_id;
        $waste_invoice->type_id = $request->type_id;
        $waste_invoice->kg = $request->kg;
        $waste_invoice->amount = $request->amount;
        $waste_invoice->picture = $request->picture;
        $waste_invoice->count = $request->count;
        $waste_invoice->save();

        return response()->json([
            'status' => true,
            'message' => 'Waste Invoice created successfully',
            'data' => $waste_invoice
        ]);
    }

    //show waste invoice by collectionID.
    public function showWasteInvoice($id, $collectionId){
        $waste_invoice = WasteInvoice::find($id);
        $collectionId = $waste_invoice->collection_id;

        if($collectionId){
            $waste = WasteInvoice::where("collection_id", $collectionId)->get();
            
            if($waste){
                return response()->json([
                    'status' => true,
                    'data' => $waste
                ]);
            } 
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'waste invoice not found'
                ]); 
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'collection id not found'
        ]);
    }

    // Route for showing all waste invoices
    public function showAllWasteInvoice(){
        $waste_invoice = WasteInvoice::all();

        if($waste_invoice->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No waste invoice found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $waste_invoice
        ]);
    }

    // Route for updating a waste invoice by ID
    public function updateWasteInvoice(Request $request, $id){
        $waste_invoice = WasteInvoice::find($id);

        if($waste_invoice === null){
            return response()->json([
                'status' => false,
                'message' => 'waste invoice not found'
            ]);
        }

        $validator = Validator::make($request->all(), [
           'collection_id' => 'required|exists:collections,id',
            'type_id' => 'required|exists:types,id',
            'kg' => 'required',
            'amount' => 'required',
            'picture' => 'required',
            'count' => 'required|integer'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        $waste_invoice = new WasteInvoice();
        $waste_invoice->collection_id = $request->collection_id;
        $waste_invoice->type_id = $request->type_id;
        $waste_invoice->kg = $request->kg;
        $waste_invoice->amount = $request->amount;
        $waste_invoice->picture = $request->picture;
        $waste_invoice->count = $request->count;
        $waste_invoice->save();

        return response()->json([
            'status' => true,
            'message' => 'Waste Invoice updated successfully',
            'data' => $waste_invoice
        ]);
    }

    //display each resident's waste invoices
    public function showResidentWasteInvoices($residentId){
        $waste_invoices = WasteInvoice::where('created_by', $residentId)->with('collection')->orderBy('created_at', 'desc')->get();

        if($waste_invoices->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'No waste invoices found for this resident'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $waste_invoices
        ]);
    }
}
