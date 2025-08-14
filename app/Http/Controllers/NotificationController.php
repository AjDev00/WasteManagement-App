<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function showNotifications($resident_id){
        $notifications = Notification::where('resident_id', $resident_id)->get();

        if($notifications){
            return response()->json([
                'status' => true,
                'data' => $notifications,
                'count' => $notifications->count()
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => "No notifications",
        ]);
    }
}
