<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    //show residents notifications
    public function showNotifications($resident_id){
        $notifications = Notification::with('picker') // eager-load picker details
                        ->where('resident_id', $resident_id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        if ($notifications->isNotEmpty()) {
            return response()->json([
                'status' => true,
                'data'   => $notifications,
                'count'  => $notifications->count(),
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => "No notifications",
        ]);
    }

    //show waste collector notifications.
    public function showWasteCollectorNotifications($waste_collector_id){
        $notifications = Notification::with('resident') // eager-load resident details
                        ->where('waste_collector_id', $waste_collector_id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        if ($notifications->isNotEmpty()) {
            return response()->json([
                'status' => true,
                'data'   => $notifications,
                'count'  => $notifications->count(),
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => "No notifications",
        ]);
    }

    //delete resident notification.
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

    //delete picker notification.
    public function deleteWasteCollectorNotification($id){
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
