<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    //Store a payment.
    public function storePayment(Request $request){
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|unique:payments,transaction_id',
            'amount' => 'required',
            'kg' => 'required',
            'type_id' => 'required|exists:types,id',
            'status' => 'required',
            'created_by' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please fix the following errors',
                'errors' => $validator->errors()
            ]);
        }

        $payment = new Payment();
        $payment->transaction_id = $request->transaction_id;
        $payment->amount = $request->amount;
        $payment->kg = $request->kg;
        $payment->type_id = $request->type_id;
        $payment->status = $request->status;
        $payment->created_by = $request->created_by;

        $payment->save();

        return response()->json([
            'status' => true,
            'message' => 'Payment Created!',
            'data' => $payment
        ]);
    }

    // Show a single payment with the ID.
    public function showPayment($id){
        $payment = Payment::find($id);

        if(!$payment){
            return response()->json([
                'status' => false,
                'message' => 'payment do not exist!',
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $payment
        ]);
    }


    // Show all payments.
    public function showAllPayment(){
        $payment = Payment::all();

        if($payment->isNotEmpty()){
            return response()->json([
                'status' => true,
                'data' => $payment
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No payment found!'
        ]);
    }

    // Update a payment.
//    public function updatePayment(Request $request, $id){
//     $payment = Payment::find($id);

//     if($payment === null){
//         return response()->json([
//             'status' => false,
//             'message' => 'payment not found'
//         ]);
//     }

//     $validator = Validator::make($request->all(), [
//        'amount' => 'required',
//         'kg' => 'required',
//         'type_id' => 'required|exists:types,id',
//         'status' => 'required',
//         'created_by' => 'required'
//     ]);

//     if($validator->fails()){
//         return response()->json([
//             'status' => false,
//             'message' => 'Please fix the following errors',
//             'errors' => $validator->errors()
//         ]);
//     }

//         $payment->amount = $request->amount;
//         $payment->kg = $request->kg;
//         $payment->type_id = $request->type_id;
//         $payment->status = $request->status;
//         $payment->created_by = $request->created_by;

//         $payment->save();
//         return response()->json([
//             'status' => true,
//             'message' => 'Updated Successfully',
//             'data' => $payment
//         ]);
//    }
}
