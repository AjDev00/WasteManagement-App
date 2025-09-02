<?php

namespace App\Http\Controllers;

use App\Models\AccountDetail;
use Illuminate\Http\Request;

class AccountDetailController extends Controller
{
    public function getResiAccountDetails($resident_id){
        $account_details = AccountDetail::where('resident_id', $resident_id)->first();

        if(!$account_details){
            return response()->json([
                'status' => false,
                'message' => 'No bank account details for this resident'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $account_details
        ]);
    }

    public function getWCAccountDetails($waste_collector_id){
        $account_details = AccountDetail::where('waste_collector_id', $waste_collector_id)->first();

        if(!$account_details){
            return response()->json([
                'status' => false,
                'message' => 'No bank account details for this resident'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $account_details
        ]);
    }
}
