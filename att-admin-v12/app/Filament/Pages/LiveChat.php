<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use App\Models\ChatMessage;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LiveChat extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|\UnitEnum|null $navigationGroup = 'Komunikasi';
    protected static ?string $navigationLabel = 'Live Chat';
    protected static ?string $title = 'Live Chat';
    protected static ?string $slug = 'live-chat';
    protected static ?int $navigationSort = 1;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected string $view = 'filament.pages.live-chat';

    public $conversations = [];
    public $activeConversationId = null;
    public $activeConversation = null;
    public $messages = [];
    public $newMessage = '';

    protected function getListeners()
    {
        return [
            'pollMessages' => 'pollMessages',
            'refreshChatList' => '$refresh',
        ];
    }

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $this->conversations = Conversation::with(['employee', 'messages' => function ($query) {
            $query->latest();
        }])->get()->sortByDesc(function ($conv) {
            return $conv->messages->first() ? $conv->messages->first()->created_at : $conv->created_at;
        });
    }

    public function selectConversation($id)
    {
        $this->activeConversationId = $id;
        $this->activeConversation = Conversation::with(['employee', 'employee.position'])->find($id);
        
        // Mark as read
        ChatMessage::where('conversation_id', $id)
            ->where('sender_type', 'employee')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->loadMessages();
        $this->loadConversations();
        
        $this->dispatch('scroll-to-bottom');
    }

    public function loadMessages()
    {
        if ($this->activeConversationId) {
            $this->messages = ChatMessage::where('conversation_id', $this->activeConversationId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->toArray();
        }
    }

    /**
     * Polling: called every N seconds from frontend to refresh messages
     */
    public function pollMessages()
    {
        if ($this->activeConversationId) {
            $this->loadMessages();
            $this->loadConversations();
        }
    }

    public function sendMessage()
    {
        if (empty(trim($this->newMessage)) || !$this->activeConversationId) {
            return;
        }

        $message = ChatMessage::create([
            'conversation_id' => $this->activeConversationId,
            'sender_type' => 'admin',
            'sender_id' => Auth::id(),
            'message' => $this->newMessage,
            'is_read' => false,
        ]);

        $this->newMessage = '';
        $this->loadMessages();
        $this->loadConversations();
        
        // Broadcast the event — wrapped in try/catch so failure doesn't break UX
        try {
            broadcast(new \App\Events\MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            Log::error('LiveChat broadcast error: ' . $e->getMessage());
        }
        
        $this->dispatch('scroll-to-bottom');
    }

    public function receiveMessage($event)
    {
        $message = $event['message'];
        
        if ($this->activeConversationId == $message['conversation_id']) {
            $this->loadMessages();
            ChatMessage::where('id', $message['id'])->update(['is_read' => true]);
            $this->dispatch('scroll-to-bottom');
        } else {
            $this->dispatch('notify', 'Pesan baru dari Karyawan');
        }
        
        $this->loadConversations();
    }
}
