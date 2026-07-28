<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user();
        
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee data not found.',
            ], 404);
        }

        $notifications = $employee->notifications()->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'body' => $notification->data['body'] ?? '',
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    public function markAsRead(Request $request)
    {
        $employee = $request->user();
        
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee data not found.',
            ], 404);
        }

        if ($request->has('notification_id')) {
            $notification = $employee->notifications()->where('id', $request->notification_id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            $employee->unreadNotifications->markAsRead();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications marked as read'
        ]);
    }
}
