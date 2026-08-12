<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use App\Services\FirebaseService;

class LeaveRequestObserver
{
    /**
     * Handle the LeaveRequest "updated" event.
     */
    public function updated(LeaveRequest $leaveRequest): void
    {
        // Check if status was changed to approved or rejected
        if ($leaveRequest->wasChanged('status')) {
            $employee = $leaveRequest->employee;
            
            if ($employee && $employee->fcm_token) {
                $statusText = $leaveRequest->status === 'approved' ? 'Disetujui' : 'Ditolak';
                
                $title = "Pengajuan Permit {$statusText}";
                $body = "Pengajuan permit Anda (Type: " . ucwords(str_replace('_', ' ', $leaveRequest->type)) . ") telah {$statusText}.";
                
                $firebase = new FirebaseService();
                $firebase->sendNotification($employee->fcm_token, $title, $body);
            }
        }
    }
}
