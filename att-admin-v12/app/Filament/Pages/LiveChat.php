<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use App\Models\ChatMessage;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class LiveChat extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|\UnitEnum|null $navigationGroup = 'Komunikasi';
    protected static ?string $navigationLabel = 'Live Chat';
    protected static ?string $title = 'Live Chat';
    protected static ?string $slug = 'live-chat';
    protected static ?int $navigationSort = 1;

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth | string | null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
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
            "echo-private:chat.{$this->activeConversationId},MessageSent" => 'receiveMessage',
            'refreshChatList' => '$refresh',
        ];
    }

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        // Load all conversations with the latest message and unread count
        $this->conversations = Conversation::with(['employee', 'messages' => function ($query) {
            $query->latest();
        }])->get()->sortByDesc(function ($conv) {
            return $conv->messages->first() ? $conv->messages->first()->created_at : $conv->created_at;
        });
    }

    public function selectConversation($id)
    {
        $this->activeConversationId = $id;
        $this->activeConversation = Conversation::with('employee')->find($id);
        
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
        
        // Broadcast the event
        broadcast(new \App\Events\MessageSent($message))->toOthers();
        
        $this->dispatch('scroll-to-bottom');
    }

    public function receiveMessage($event)
    {
        $message = $event['message'];
        
        if ($this->activeConversationId == $message['conversation_id']) {
            // If the chat is active, append it and mark as read
            $this->loadMessages();
            ChatMessage::where('id', $message['id'])->update(['is_read' => true]);
            $this->dispatch('scroll-to-bottom');
        } else {
            // Otherwise just notify and refresh list
            $this->dispatch('notify', 'Pesan baru dari Karyawan');
        }
        
        $this->loadConversations();
    }
}
