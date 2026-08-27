<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\Employee;
use App\Services\FirebaseService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LiveChat extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|\UnitEnum|null $navigationGroup = 'Communication';
    protected static ?string $navigationLabel = 'Live Chat';
    protected static ?string $title = 'Live Chat';
    protected static ?string $slug = 'live-chat';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('view_live_chat'));
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected string $view = 'filament.pages.live-chat';

    public $activeConversationId = null;
    public $messages = [];
    public $newMessage = '';
    public $search = '';
    public $showNewChatModal = false;
    public $newChatSearch = '';

    protected function getListeners()
    {
        return [
            'pollMessages' => 'pollMessages',
            'refreshChatList' => '$refresh',
        ];
    }

    public function mount()
    {
        // Auto-select first conversation if available so the input field & chat are immediately visible
        $firstConv = Conversation::latest('updated_at')->first();
        if ($firstConv) {
            $this->selectConversation($firstConv->id);
        }
    }

    public function selectConversation($id)
    {
        $this->activeConversationId = (int)$id;
        
        // Mark employee messages as read
        ChatMessage::where('conversation_id', $id)
            ->where('sender_type', 'employee')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->loadMessages();
        $this->dispatch('scroll-to-bottom');
    }

    public function startConversationWithEmployee($employeeId)
    {
        $conversation = Conversation::firstOrCreate([
            'employee_id' => $employeeId,
        ]);

        $this->showNewChatModal = false;
        $this->newChatSearch = '';
        $this->selectConversation($conversation->id);
    }

    public function openNewChatModal()
    {
        $this->showNewChatModal = true;
        $this->newChatSearch = '';
    }

    public function closeNewChatModal()
    {
        $this->showNewChatModal = false;
    }

    public function loadMessages()
    {
        if ($this->activeConversationId) {
            $this->messages = ChatMessage::where('conversation_id', $this->activeConversationId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->toArray();
        } else {
            $this->messages = [];
        }
    }

    public function pollMessages()
    {
        if ($this->activeConversationId) {
            $this->loadMessages();
        }
    }

    public function sendMessage()
    {
        $text = trim($this->newMessage);
        if (empty($text) || !$this->activeConversationId) {
            return;
        }

        $message = ChatMessage::create([
            'conversation_id' => $this->activeConversationId,
            'sender_type' => 'admin',
            'sender_id' => Auth::id(),
            'message' => $text,
            'is_read' => false,
        ]);

        // Touch conversation to refresh updated_at
        Conversation::where('id', $this->activeConversationId)->touch();

        $this->newMessage = '';
        $this->loadMessages();

        $conversation = Conversation::with('employee')->find($this->activeConversationId);
            
        // Send FCM push notification and Broadcast in background
        app()->terminating(function () use ($text, $conversation, $message) {
            try {
                $employee = $conversation?->employee;
                if ($employee && !empty($employee->fcm_token)) {
                    $firebase = new FirebaseService();
                    $firebase->sendNotification(
                        $employee->fcm_token,
                        'Pesan dari Admin',
                        $text,
                        [
                            'type' => 'chat',
                            'conversation_id' => (string) $conversation->id,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::error('LiveChat FCM notification error: ' . $e->getMessage());
            }
            
            try {
                broadcast(new \App\Events\MessageSent($message))->toOthers();
            } catch (\Throwable $e) {
                Log::error('LiveChat broadcast error: ' . $e->getMessage());
            }
        });
        
        $this->dispatch('scroll-to-bottom');
    }

    public function getConversations()
    {
        $query = Conversation::with([
            'employee', 
            'employee.position', 
            'employee.area', 
            'employee.branch', 
            'messages' => function ($q) {
                $q->latest();
            }
        ]);

        if (!empty($this->search)) {
            $query->whereHas('employee', function ($q) {
                $q->whereRaw('LOWER(full_name) LIKE LOWER(?)', ['%' . strtolower($this->search) . '%'])
                  ->orWhereRaw('LOWER(employee_no) LIKE LOWER(?)', ['%' . strtolower($this->search) . '%']);
            });
        }

        return $query->get()->sortByDesc(function ($conv) {
            return $conv->messages->first() ? $conv->messages->first()->created_at : $conv->updated_at;
        });
    }

    public function getAvailableEmployees()
    {
        $query = Employee::where('is_active', true)->with(['position', 'branch']);

        if (!empty($this->newChatSearch)) {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(full_name) LIKE LOWER(?)', ['%' . strtolower($this->newChatSearch) . '%'])
                  ->orWhereRaw('LOWER(employee_no) LIKE LOWER(?)', ['%' . strtolower($this->newChatSearch) . '%']);
            });
        }

        return $query->orderBy('full_name')->limit(25)->get();
    }

    protected function getViewData(): array
    {
        $conversations = $this->getConversations();
        $activeConversation = $this->activeConversationId 
            ? Conversation::with(['employee', 'employee.position', 'employee.area', 'employee.branch'])->find($this->activeConversationId)
            : null;
        $availableEmployees = $this->showNewChatModal ? $this->getAvailableEmployees() : collect();

        return [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'availableEmployees' => $availableEmployees,
        ];
    }
}
