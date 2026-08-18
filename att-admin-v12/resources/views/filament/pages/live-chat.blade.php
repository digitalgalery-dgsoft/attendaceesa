<x-filament-panels::page>
    <style>
        .live-chat-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            height: 75vh;
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        @media (min-width: 768px) {
            .live-chat-container {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .chat-sidebar {
            grid-column: span 1 / span 1;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            height: 100%;
            background-color: #f9fafb;
        }
        .chat-sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background-color: #ffffff;
        }
        .chat-sidebar-list {
            flex: 1 1 0%;
            overflow-y: auto;
            padding: 0.5rem;
        }
        .chat-item {
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background-color 0.2s;
            margin-bottom: 0.5rem;
        }
        .chat-item:hover {
            background-color: #f3f4f6;
        }
        .chat-item.active {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
        }
        .chat-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            background-color: #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4b5563;
            font-weight: 700;
            flex-shrink: 0;
            margin-right: 0.75rem;
        }
        .chat-avatar.active {
            background-color: #3b82f6;
            color: #ffffff;
        }
        .chat-main {
            grid-column: span 1 / span 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
            background-color: #f3f4f6;
        }
        @media (min-width: 768px) {
            .chat-main {
                grid-column: span 2 / span 2;
            }
        }
        .chat-main-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            z-index: 10;
        }
        .chat-messages {
            flex: 1 1 0%;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .chat-message-row {
            display: flex;
            width: 100%;
        }
        .chat-message-row.admin {
            justify-content: flex-end;
        }
        .chat-message-row.user {
            justify-content: flex-start;
        }
        .chat-bubble {
            max-width: 75%;
            border-radius: 1rem;
            padding: 0.5rem 1rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .chat-bubble.admin {
            background-color: #3b82f6;
            color: #ffffff;
            border-top-right-radius: 0;
        }
        .chat-bubble.user {
            background-color: #ffffff;
            border-top-left-radius: 0;
            border: 1px solid #e5e7eb;
        }
        .chat-time {
            font-size: 0.625rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.25rem;
        }
        .chat-bubble.admin .chat-time {
            color: #dbeafe;
        }
        .chat-bubble.user .chat-time {
            color: #9ca3af;
        }
        .chat-input-area {
            padding: 1rem;
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
        }
        .chat-input-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .chat-input {
            flex: 1 1 0%;
            border-radius: 9999px;
            border: 1px solid #d1d5db;
            padding: 0.5rem 1rem;
            outline: none;
            font-size: 0.875rem;
        }
        .chat-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }
        .chat-send-btn {
            background-color: #3b82f6;
            color: #ffffff;
            border-radius: 9999px;
            padding: 0.5rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
        }
        .chat-send-btn:hover {
            background-color: #2563eb;
        }
        .chat-send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .chat-empty {
            flex: 1 1 0%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
        }
        .chat-empty svg {
            width: 5rem;
            height: 5rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .poll-indicator {
            font-size: 0.65rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-top: 1px solid #f3f4f6;
        }
        .poll-dot {
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background-color: #10b981;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        /* Dark mode overrides */
        .dark .live-chat-container, .dark .chat-sidebar-header, .dark .chat-main-header, .dark .chat-input-area {
            background-color: #111827;
            border-color: #374151;
            color: #f3f4f6;
        }
        .dark .chat-sidebar {
            background-color: rgba(31, 41, 55, 0.5);
            border-color: #374151;
        }
        .dark .chat-item:hover {
            background-color: #1f2937;
        }
        .dark .chat-item.active {
            background-color: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.3);
        }
        .dark .chat-main {
            background-color: rgba(17, 24, 39, 0.5);
        }
        .dark .chat-bubble.user {
            background-color: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }
        .dark .chat-input {
            background-color: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }
        .dark .poll-indicator {
            border-color: #1f2937;
        }
    </style>

    <div class="live-chat-container" 
         x-data="{
            pollInterval: null,
            activeConvId: {{ $activeConversationId ?? 'null' }},
            startPolling() {
                this.stopPolling();
                if (this.activeConvId) {
                    this.pollInterval = setInterval(() => {
                        $wire.pollMessages();
                    }, 3000); // Poll every 3 seconds
                }
            },
            stopPolling() {
                if (this.pollInterval) {
                    clearInterval(this.pollInterval);
                    this.pollInterval = null;
                }
            },
            scrollToBottom() {
                let container = $refs.chatContainer;
                if(container) {
                    container.scrollTop = container.scrollHeight;
                }
            }
         }" 
         x-init="
            // Start polling if there's already an active conversation
            if (activeConvId) startPolling();

            $wire.on('scroll-to-bottom', () => {
                setTimeout(() => scrollToBottom(), 100);
            });
            $wire.on('notify', (message) => {
                new FilamentNotification()
                    .title(message)
                    .success()
                    .send();
            });
         "
         @conversation-selected.window="
            activeConvId = $event.detail.id;
            startPolling();
         "
         x-on:livewire:navigating.window="stopPolling()"
    >
        
        {{-- Sidebar: List of Conversations --}}
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h3 style="font-size: 1.125rem; font-weight: 700;">Percakapan</h3>
                <div style="margin-top: 0.75rem;">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama karyawan..." class="chat-input" style="width: 100%;">
                </div>
            </div>
            <div class="chat-sidebar-list">
                @forelse($conversations as $conversation)
                    @php
                        $latest = $conversation->messages->first();
                        // Hitung unread dari koleksi yang sudah di-eager-load (tanpa N+1 query)
                        $unread = $conversation->messages->filter(fn($m) => $m->sender_type === 'employee' && !$m->is_read)->count();
                        $isActive = $activeConversationId === $conversation->id;
                        $employeeName = $conversation->employee->full_name ?? 'Karyawan';
                        $empPos = optional($conversation->employee->position)->name ?? '';
                        $empAr = optional($conversation->employee->area)->name ?? '';
                        $posArea = array_filter([$empPos, $empAr]);
                        $subtitle = implode(' • ', $posArea);
                        $initial = strtoupper(substr($employeeName, 0, 1));
                    @endphp
                    <div wire:click="selectConversation({{ $conversation->id }})" 
                         x-on:click="activeConvId = {{ $conversation->id }}; startPolling();"
                         class="chat-item {{ $isActive ? 'active' : '' }}">
                        <div style="display: flex; align-items: center; overflow: hidden;">
                            <div class="chat-avatar {{ $isActive ? 'active' : '' }}">
                                {{ $initial }}
                            </div>
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <h4 style="font-weight: 600; font-size: 0.875rem; {{ $isActive ? 'color: #2563eb;' : '' }}">
                                    {{ $employeeName }}
                                </h4>
                                @if(!empty($subtitle))
                                <div style="font-size: 0.65rem; color: #9ca3af; margin-bottom: 0.125rem;">
                                    {{ $subtitle }}
                                </div>
                                @endif
                                <p style="font-size: 0.75rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $latest ? \Illuminate\Support\Str::limit($latest->message, 30) : 'Belum ada pesan' }}
                                </p>
                            </div>
                        </div>
                        @if($unread > 0)
                            <div style="background-color: #ef4444; color: white; font-size: 0.75rem; font-weight: 700; padding: 0.125rem 0.5rem; border-radius: 9999px; flex-shrink: 0;">
                                {{ $unread }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="text-align: center; padding: 1rem; color: #6b7280; font-size: 0.875rem;">
                        Belum ada percakapan
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Main Chat Area --}}
        <div class="chat-main">
            @if($activeConversation)
                @php
                    $empName = $activeConversation->employee->full_name ?? 'Karyawan';
                    $empPosition = optional($activeConversation->employee->position)->name ?? '';
                    $empArea = optional($activeConversation->employee->area)->name ?? null;
                    $empInitial = strtoupper(substr($empName, 0, 1));
                @endphp
                {{-- Header --}}
                <div class="chat-main-header">
                    <div class="chat-avatar active" style="margin-right: 0.75rem;">
                        {{ $empInitial }}
                    </div>
                    <div>
                        <h3 style="font-weight: 700; font-size: 0.95rem;">{{ $empName }}</h3>
                        <p style="font-size: 0.75rem; color: #6b7280;">
                            {{ $empPosition }}{{ $empArea ? ' • ' . $empArea : '' }}
                        </p>
                    </div>
                </div>

                {{-- Messages List --}}
                <div class="chat-messages" x-ref="chatContainer">
                    @forelse($messages as $msg)
                        @php
                            $isAdmin = $msg['sender_type'] === 'admin';
                        @endphp
                        <div class="chat-message-row {{ $isAdmin ? 'admin' : 'user' }}">
                            <div class="chat-bubble {{ $isAdmin ? 'admin' : 'user' }}">
                                <p style="font-size: 0.875rem; white-space: pre-wrap; margin: 0;">{{ $msg['message'] }}</p>
                                <div class="chat-time">
                                    <span>{{ \Carbon\Carbon::parse($msg['created_at'])->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i') }}</span>
                                    @if($isAdmin)
                                        <svg style="width: 0.75rem; height: 0.75rem; {{ $msg['is_read'] ? 'color: #93c5fd;' : 'color: #bfdbfe;' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; color: #6b7280; font-size: 0.875rem; margin-top: 2.5rem;">
                            Mulai obrolan dengan {{ $empName }}
                        </div>
                    @endforelse
                </div>

                {{-- Polling status indicator --}}
                <div class="poll-indicator">
                    <div class="poll-dot"></div>
                    <span>Auto-refresh aktif • setiap 3 detik</span>
                </div>

                {{-- Input Area --}}
                <div class="chat-input-area">
                    <form wire:submit.prevent="sendMessage" class="chat-input-form">
                        <input 
                            type="text" 
                            wire:model="newMessage" 
                            placeholder="Ketik pesan..." 
                            class="chat-input"
                            wire:keydown.enter="sendMessage"
                        >
                        <button type="submit" class="chat-send-btn" wire:loading.attr="disabled" wire:target="sendMessage">
                            <svg style="width: 1.25rem; height: 1.25rem; transform: rotate(90deg);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="chat-empty">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                    <p style="font-size: 1.125rem; font-weight: 500;">Pilih percakapan untuk mulai chat</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
