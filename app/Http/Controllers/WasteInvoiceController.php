<?php

namespace App\Http\Controllers;

use App\Models\Type;
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

    // public function deleteWasteInvoice($id){
    //     $notification = WasteInvoice::find($id);

    //     if(!$notification){
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Notification not found!'
    //         ]);
    //     }

    //     $notification->delete();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Notification deleted!'
    //     ]);

    // }

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
        $waste_invoices = WasteInvoice::where('created_by', $residentId)
                        ->with('collection')
                        ->orderBy('created_at', 'desc')
                        ->get();

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

    //display the first five waste invoices for a resident
    public function showFirstFiveWasteInvoices($residentId){
        $waste_invoices = WasteInvoice::where('created_by', $residentId)
                        ->with('collection')
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();

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

    public function filterWasteInvoiceByType($type, $resident_id){
        $waste_type = Type::where('title', $type)->first();
        $type_id = $waste_type->id;
        $ty = WasteInvoice::where('type_id', $type_id)
            ->where('created_by', $resident_id)
            ->with('collection')
            ->get();

        return response()->json([
            'data' => $ty
        ]);

    }
    
    public function filterByPendingStatus($id){
        $waste = WasteInvoice::where('status', 'pending')
                ->where('created_by', $id)
                ->with('collection')
                ->latest()
                ->get();

        if($waste->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $waste
        ]);
    }

    public function filterByVerifiedStatus($id){
        $waste = WasteInvoice::where('status', 'verified')
                ->where('created_by', $id)
                ->with('collection')
                ->latest()
                ->get();

        if($waste->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $waste
        ]);
    }

    public function filterByDeliveredStatus($id){
        $waste = WasteInvoice::where('status', 'delivered')
                ->where('created_by', $id)
                ->with('collection')
                ->latest()
                ->get();

        if($waste->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $waste
        ]);
    }

    public function filterByPaidStatus($id){
        $waste = WasteInvoice::where('status', 'paid')
                ->where('created_by', $id)
                ->with('collection')
                ->latest()
                ->get();

        if($waste->isEmpty()){
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $waste
        ]);
    }

    // public function getTotalAmountOfWasteInvoices($resident_id){
    //     $total = WasteInvoice::where('created_by', $resident_id)->count();

    //     if($total === 0){
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No waste invoices found for this resident'
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'data' => $total
    //     ]);
    // }

    // public function getTotalAmountOfPlasticWasteInvoices($resident_id){
    //     $total = WasteInvoice::where('created_by', $resident_id)
    //                 ->where('type_id', Type::where('title', 'Plastic')->first()->id)
    //                 ->count();

    //     if($total === 0){
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No plastic waste invoices found for this resident'
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'data' => $total
    //     ]);
    // }

    // public function getTotalAmountOfOrganicWasteInvoices($resident_id){
    //     $total = WasteInvoice::where('created_by', $resident_id)
    //                 ->where('type_id', Type::where('title', 'Organic')->first()->id)
    //                 ->count();

    //     if($total === 0){
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No organic waste invoices found for this resident'
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'data' => $total
    //     ]);
    // }

    // public function getTotalAmountOfCansWasteInvoices($resident_id){
    //     $total = WasteInvoice::where('created_by', $resident_id)
    //                 ->where('type_id', Type::where('title', 'Cans')->first()->id)
    //                 ->count();

    //     if($total === 0){
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No cans waste invoices found for this resident'
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'data' => $total
    //     ]);
    // }

    // public function getTotalAmountOfEWasteInvoices($resident_id){
    //     $total = WasteInvoice::where('created_by', $resident_id)
    //                 ->where('type_id', Type::where('title', 'E-Waste')->first()->id)
    //                 ->count();

    //     if($total === 0){
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No e-waste invoices found for this resident'
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'data' => $total
    //     ]);
    // }
}