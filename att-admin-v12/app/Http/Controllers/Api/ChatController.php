<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Fetch conversation messages for the authenticated employee.
     */
    public function getMessages(Request $request)
    {
        $employeeId = $request->user()->id; // Get ID from sanctum authenticated user

        $conversation = Conversation::firstOrCreate([
            'employee_id' => $employeeId,
        ]);

        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $messages,
        ]);
    }

    /**
     * Send a new message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $employeeId = $request->user()->id;

        $conversation = Conversation::firstOrCreate([
            'employee_id' => $employeeId,
        ]);

        $message = $conversation->messages()->create([
            'sender_type' => 'employee',
            'sender_id' => $employeeId,
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Broadcast the event
        try {
            broadcast(new MessageSent($message));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Chat broadcast error: ' . $e->getMessage());
        }

        // Kirim notifikasi database ke semua admin agar muncul di lonceng Filament
        try {
            $employee = $request->user(); // Employee model (Sanctum)
            $employeeName = $employee->full_name ?? $employee->name ?? 'Karyawan';
            
            $admins = \App\Models\User::all();
            \Illuminate\Support\Facades\Log::info('Sending Filament notification to ' . $admins->count() . ' admins for: ' . $employeeName);
            
            if ($admins->isNotEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->title('💬 Pesan baru dari ' . $employeeName)
                    ->body(\Illuminate\Support\Str::limit($request->message, 50))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->success()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Balas')
                            ->url(url('/admin/live-chat'))
                            ->button()
                    ])
                    ->sendToDatabase($admins);
                \Illuminate\Support\Facades\Log::info('Filament notification sent successfully.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Filament notification error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }

        return response()->json([
            'status' => 'success',
            'data' => $message,
        ]);
    }

    /**
     * Mark messages from admin as read.
     */
    public function markAsRead(Request $request)
    {
        $employeeId = $request->user()->id;

        $conversation = Conversation::where('employee_id', $employeeId)->first();

        if ($conversation) {
            $conversation->messages()
                ->where('sender_type', 'admin')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json([
            'status' => 'success',
        ]);
    }
    /**
     * Get unread message count for badge display.
     */
    public function getUnreadCount(Request $request)
    {
        $employeeId = $request->user()->id;

        $conversation = Conversation::where('employee_id', $employeeId)->first();

        $count = 0;
        if ($conversation) {
            $count = $conversation->messages()
                ->where('sender_type', 'admin')
                ->where('is_read', false)
                ->count();
        }

        return response()->json([
            'status' => 'success',
            'data' => ['unread_count' => $count],
        ]);
    }
}
