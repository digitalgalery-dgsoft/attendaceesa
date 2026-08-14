<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function unreadMessagesCount()
    {
        return $this->messages()->where('sender_type', 'employee')->where('is_read', false)->count();
    }
}
