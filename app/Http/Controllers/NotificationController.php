<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function showNotifications($resident_id){
        $notifications = Notification::where('resident_id', $resident_id)->orderBy('created_at', 'desc')->get();

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

    public function deleteNotification($id){
        $notification = Notification::find($id);

        if(!$notification){
            return response()->json([
                'status' => false,
                'message' => 'Notification not found!'
            ]);
        }

        $notification->delete();

        return response()->json([
            'status' => true,
            'message' => 'Notification deleted!'
        ]);

    }

    public function isRead($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found!'
            ]);
        }

        $notification->update(['is_read' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read!'
        ]);
    }
}
