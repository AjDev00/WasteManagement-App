<?php

namespace App\Http\Controllers;

use App\Models\Earning;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;

class WithdrawalRequestController extends Controller
{
    public function storeResiWithdrawalRequest(Request $request){
        $withdrawDetails = $request->validate([
            'resident_id' => 'nullable|exists:residents,id',
            'waste_collector_id' => 'nullable|exists:waste_collectors,id',
            'bank_name' => 'required|string',
            'bank_account_number' => 'required|string',
            'account_name' => 'required|string',
            'amount' => 'required|numeric|min:10',
        ]);

        $earning = Earning::where('resident_id', $withdrawDetails['resident_id'])
            ->latest()
            ->first();

        if (!$earning) {
            return response()->json([
                'status' => false,
                'message' => 'Earning record not found.'
            ], 404);
        }

        // ✅ check balance before creating withdrawal
        if ($earning->total_earning < $withdrawDetails['amount']) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance for withdrawal'
            ], 400);
        }

        // ✅ only create withdrawal if balance is enough
        $withdraw = WithdrawalRequest::create([
            'resident_id'        => $withdrawDetails['resident_id'],
            'waste_collector_id' => $withdrawDetails['waste_collector_id'],
            'bank_name'          => $withdrawDetails['bank_name'],
            'bank_account_number'=> $withdrawDetails['bank_account_number'],
            'account_name'       => $withdrawDetails['account_name'],
            'amount'             => $withdrawDetails['amount'],
        ]);

        // deduct balance
        $earning->update([
            'total_earning' => $earning->total_earning - $withdrawDetails['amount']
        ]);

        // create notification
        Notification::create([
            'resident_id'        => $withdraw->resident_id,
            'waste_collector_id' => null,
            'title'              => 'Withdrawal Request',
            'message'            => "Your withdrawal request of {$withdrawDetails['amount']} WSC is processing. Check your transactions to monitor.",
            'message_type'       => 'withdrawal_request',
        ]);

        //create transaction receipt for resident.
        Transaction::create([
            'resident_id' => $withdraw->resident_id,
            'waste_collector_id' => null,
            'title' => 'Points Redeemed',
            'description' => 'your points has been redeemed by Seikula',
            'amount' => $withdrawDetails['amount'],
            'status' => 'Pending'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Withdrawal request made successfully!',
            'data' => $withdraw,
            'remaining_balance' => $earning->total_earning
        ]);
    }

    public function storePickerWithdrawalRequest(Request $request){
        $withdrawDetails = $request->validate([
            'resident_id' => 'nullable|exists:residents,id',
            'waste_collector_id' => 'nullable|exists:waste_collectors,id',
            'bank_name' => 'required|string',
            'bank_account_number' => 'required|string',
            'account_name' => 'required|string',
            'amount' => 'required|numeric|min:10',
        ]);

        $earning = Earning::where('waste_collector_id', $withdrawDetails['waste_collector_id'])
            ->latest()
            ->first();

        if (!$earning) {
            return response()->json([
                'status' => false,
                'message' => 'Earning record not found.'
            ], 404);
        }

        // check balance first
        if ($withdrawDetails['amount'] > $earning->total_earning) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance for withdrawal'
            ], 400);
        }

        // ✅ now we can safely create the withdrawal request
        $withdraw = WithdrawalRequest::create([
            'resident_id'        => $withdrawDetails['resident_id'],
            'waste_collector_id' => $withdrawDetails['waste_collector_id'],
            'bank_name'          => $withdrawDetails['bank_name'],
            'bank_account_number'=> $withdrawDetails['bank_account_number'],
            'account_name'       => $withdrawDetails['account_name'],
            'amount'             => $withdrawDetails['amount'],
        ]);

        // deduct balance
        $earning->update([
            'total_earning' => $earning->total_earning - $withdrawDetails['amount']
        ]);

        // create notification
        Notification::create([
            'resident_id'        => null,
            'waste_collector_id' => $withdraw->waste_collector_id,
            'title'              => 'Withdrawal Request',
            'message'            => "Your withdrawal request of {$withdrawDetails['amount']} WSC is processing. Check your transactions to monitor.",
            'message_type'       => 'withdrawal_request',
        ]);

        //create transaction receipt for picker.
        Transaction::create([
            'resident_id' => null,
            'waste_collector_id' => $withdraw->waste_collector_id,
            'title' => 'Points Redeemed',
            'description' => 'your points has been redeemed by Seikula',
            'amount' => $withdrawDetails['amount'],
            'status' => 'Pending'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Withdrawal request made successfully!',
            'data' => $withdraw,
            'remaining_balance' => $earning->total_earning
        ]);
    }
}
