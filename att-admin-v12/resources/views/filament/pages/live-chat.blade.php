<x-filament-panels::page>
    <style>
        .live-chat-wrapper {
            display: grid;
            grid-template-columns: 340px 1fr;
            height: calc(100vh - 200px);
            min-height: 560px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05);
        }

        /* Dark mode support */
        .dark .live-chat-wrapper {
            background: #111827;
            border-color: #374151;
        }

        /* SIDEBAR */
        .chat-sidebar {
            border-right: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }
        .dark .chat-sidebar {
            background: #182234;
            border-color: #374151;
        }

        .chat-sidebar-top {
            padding: 1.15rem 1rem 0.85rem;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .dark .chat-sidebar-top {
            background: #111827;
            border-color: #374151;
        }

        .sidebar-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .sidebar-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .dark .sidebar-title {
            color: #f1f5f9;
        }

        .btn-new-chat {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.35rem 0.75rem;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-new-chat:hover {
            background: #1d4ed8;
        }

        .chat-search-input {
            width: 100%;
            padding: 0.5rem 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 0.82rem;
            outline: none;
            color: inherit;
        }
        .chat-search-input:focus {
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }
        .dark .chat-search-input {
            background: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }

        .chat-conv-list {
            flex: 1 1 0%;
            overflow-y: auto;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .chat-conv-item {
            padding: 0.75rem 0.85rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
            border: 1px solid transparent;
        }
        .chat-conv-item:hover {
            background: #f1f5f9;
        }
        .dark .chat-conv-item:hover {
            background: #1f2937;
        }
        .chat-conv-item.active {
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .dark .chat-conv-item.active {
            background: rgba(37, 99, 235, 0.15);
            border-color: rgba(37, 99, 235, 0.4);
        }

        .conv-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 800;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .chat-conv-item.active .conv-avatar {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #ffffff;
        }

        .conv-info {
            flex: 1 1 0%;
            min-width: 0;
        }
        .conv-name-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .conv-name {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .conv-name {
            color: #f1f5f9;
        }
        .chat-conv-item.active .conv-name {
            color: #1d4ed8;
        }
        .dark .chat-conv-item.active .conv-name {
            color: #60a5fa;
        }
        .conv-time {
            font-size: 0.68rem;
            color: #94a3b8;
            font-weight: 500;
        }
        .conv-meta {
            font-size: 0.72rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        .dark .conv-meta {
            color: #94a3b8;
        }
        .conv-preview {
            font-size: 0.76rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .conv-preview {
            color: #94a3b8;
        }

        .unread-badge {
            background: #ef4444;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 9999px;
            flex-shrink: 0;
        }

        /* MAIN CHAT PANE */
        .chat-main-pane {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #f8fafc;
            overflow: hidden;
        }
        .dark .chat-main-pane {
            background: #0f172a;
        }

        .chat-main-header {
            padding: 0.85rem 1.5rem;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            z-index: 10;
        }
        .dark .chat-main-header {
            background: #111827;
            border-color: #374151;
        }

        .header-user-info {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        .header-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #ffffff;
            font-weight: 800;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
        }

        /* MESSAGE STREAM */
        .chat-messages-stream {
            flex: 1 1 0%;
            overflow-y: auto;
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .msg-row {
            display: flex;
            width: 100%;
        }
        .msg-row.admin {
            justify-content: flex-end;
        }
        .msg-row.user {
            justify-content: flex-start;
        }

        .msg-bubble {
            max-width: 70%;
            padding: 0.65rem 1rem;
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            position: relative;
        }
        .msg-bubble.admin {
            background: #2563eb;
            color: #ffffff;
            border-bottom-right-radius: 3px;
        }
        .msg-bubble.user {
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 3px;
        }
        .dark .msg-bubble.user {
            background: #1e293b;
            color: #f1f5f9;
            border-color: #334155;
        }

        .msg-text {
            font-size: 0.88rem;
            line-height: 1.45;
            white-space: pre-wrap;
            word-break: break-word;
            margin: 0;
        }

        .msg-footer {
            font-size: 0.65rem;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
            opacity: 0.85;
        }
        .msg-bubble.admin .msg-footer {
            color: #bfdbfe;
        }
        .msg-bubble.user .msg-footer {
            color: #94a3b8;
        }

        /* CHAT INPUT AREA (ALWAYS VISIBLE) */
        .chat-input-wrapper {
            padding: 1rem 1.5rem;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.02);
            z-index: 10;
        }
        .dark .chat-input-wrapper {
            background: #111827;
            border-color: #374151;
        }

        .chat-form-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            padding: 0.35rem 0.5rem 0.35rem 1rem;
            transition: all 0.15s ease;
        }
        .chat-form-box:focus-within {
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .dark .chat-form-box {
            background: #1f2937;
            border-color: #4b5563;
        }
        .dark .chat-form-box:focus-within {
            background: #111827;
            border-color: #3b82f6;
        }

        .chat-text-input {
            flex: 1 1 0%;
            border: none;
            background: transparent;
            font-size: 0.88rem;
            outline: none;
            color: inherit;
            padding: 0.4rem 0;
            font-family: inherit;
        }

        .btn-send-message {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            transition: all 0.15s ease;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        }
        .btn-send-message:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-send-message:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* EMPTY STATE */
        .chat-empty-state {
            flex: 1 1 0%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
            color: #94a3b8;
        }
        .chat-empty-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }
        .dark .chat-empty-icon {
            color: #374151;
        }

        /* MODAL MULAI CHAT BARU */
        .new-chat-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .new-chat-modal-card {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
            animation: modalFadeIn 0.2s ease;
        }
        .dark .new-chat-modal-card {
            background: #1f2937;
            border: 1px solid #374151;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-card-header {
            padding: 1.15rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .modal-card-header {
            border-color: #374151;
        }

        .modal-emp-list {
            overflow-y: auto;
            max-height: 380px;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .modal-emp-item {
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.12s ease;
        }
        .modal-emp-item:hover {
            background: #f1f5f9;
        }
        .dark .modal-emp-item:hover {
            background: #374151;
        }

        @media (max-width: 900px) {
            .live-chat-wrapper {
                grid-template-columns: 1fr;
                height: auto;
                min-height: 600px;
            }
            .chat-sidebar {
                max-height: 250px;
            }
        }
    </style>

    <div class="live-chat-wrapper"
         x-data="{
            pollInterval: null,
            activeConvId: @entangle('activeConversationId'),
            startPolling() {
                this.stopPolling();
                this.pollInterval = setInterval(() => {
                    $wire.pollMessages();
                }, 3000);
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
            startPolling();
            $wire.on('scroll-to-bottom', () => {
                setTimeout(() => scrollToBottom(), 100);
            });
         "
         x-on:livewire:navigating.window="stopPolling()"
    >
        {{-- SIDEBAR: CONVERSATION LIST --}}
        <div class="chat-sidebar">
            <div class="chat-sidebar-top">
                <div class="sidebar-title-row">
                    <span class="sidebar-title">Percakapan</span>
                    <button type="button" class="btn-new-chat" wire:click="openNewChatModal">
                        <i class="fa-solid fa-plus"></i> Chat Baru
                    </button>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="🔍 Cari nama karyawan / NIK..." class="chat-search-input">
            </div>

            <div class="chat-conv-list">
                @forelse($conversations as $conv)
                    @php
                        $latest = $conv->messages->first();
                        $unread = $conv->messages->filter(fn($m) => $m->sender_type === 'employee' && !$m->is_read)->count();
                        $isActive = $activeConversationId == $conv->id;
                        $empName = $conv->employee?->full_name ?? $conv->employee?->name ?? 'Karyawan';
                        $empPos = $conv->employee?->position?->name ?? '';
                        $empBranch = $conv->employee?->branch?->name ?? $conv->employee?->area?->name ?? '';
                        $initial = strtoupper(substr($empName, 0, 1));
                    @endphp
                    <div wire:click="selectConversation({{ $conv->id }})" 
                         class="chat-conv-item {{ $isActive ? 'active' : '' }}">
                        <div class="conv-avatar">
                            {{ $initial }}
                        </div>
                        <div class="conv-info">
                            <div class="conv-name-row">
                                <span class="conv-name">{{ $empName }}</span>
                                @if($latest)
                                    <span class="conv-time">{{ $latest->created_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i') }}</span>
                                @endif
                            </div>
                            <div class="conv-meta">
                                {{ $empPos }}{{ $empBranch ? ' • ' . $empBranch : '' }}
                            </div>
                            <div class="conv-preview">
                                {{ $latest ? \Illuminate\Support\Str::limit($latest->message, 32) : 'Belum ada pesan' }}
                            </div>
                        </div>
                        @if($unread > 0)
                            <div class="unread-badge">
                                {{ $unread }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="text-align: center; padding: 2.5rem 1rem; color: #94a3b8; font-size: 0.85rem;">
                        <i class="fa-regular fa-comment-dots" style="font-size: 2rem; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                        <p>Belum ada riwayat chat</p>
                        <button type="button" wire:click="openNewChatModal" style="margin-top: 0.75rem; background: none; border: none; color: #2563eb; font-weight: 700; font-size: 0.8rem; cursor: pointer;">
                            + Mulai Percakapan Baru
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- MAIN CHAT PANE --}}
        <div class="chat-main-pane">
            @if($activeConversation)
                @php
                    $activeEmpName = $activeConversation->employee?->full_name ?? $activeConversation->employee?->name ?? 'Karyawan';
                    $activeEmpPos = $activeConversation->employee?->position?->name ?? 'Tenaga Lapangan';
                    $activeEmpBranch = $activeConversation->employee?->branch?->name ?? $activeConversation->employee?->area?->name ?? 'Area';
                    $activeEmpNik = $activeConversation->employee?->employee_no ?? $activeConversation->employee?->nik ?? '-';
                    $activeEmpInitial = strtoupper(substr($activeEmpName, 0, 1));
                @endphp

                {{-- Header --}}
                <div class="chat-main-header">
                    <div class="header-user-info">
                        <div class="header-avatar">
                            {{ $activeEmpInitial }}
                        </div>
                        <div>
                            <h3 style="font-weight: 800; font-size: 1rem; color: #0f172a; margin: 0;">{{ $activeEmpName }}</h3>
                            <p style="font-size: 0.76rem; color: #64748b; margin: 2px 0 0 0;">
                                {{ $activeEmpPos }} &bull; {{ $activeEmpBranch }} (NIK/No: {{ $activeEmpNik }})
                            </p>
                        </div>
                    </div>
                    <div>
                        <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.75rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 0.25rem 0.65rem; border-radius: 9999px;">
                            <span style="width: 6px; height: 6px; border-radius: 9999px; background: #16a34a;"></span> Terhubung
                        </span>
                    </div>
                </div>

                {{-- Messages List --}}
                <div class="chat-messages-stream" x-ref="chatContainer">
                    @forelse($messages as $msg)
                        @php
                            $isAdmin = ($msg['sender_type'] ?? '') === 'admin';
                            $timeStr = \Carbon\Carbon::parse($msg['created_at'])->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i');
                        @endphp
                        <div class="msg-row {{ $isAdmin ? 'admin' : 'user' }}">
                            <div class="msg-bubble {{ $isAdmin ? 'admin' : 'user' }}">
                                <p class="msg-text">{{ $msg['message'] }}</p>
                                <div class="msg-footer">
                                    <span>{{ $timeStr }}</span>
                                    @if($isAdmin)
                                        <i class="fa-solid fa-check-double" style="font-size: 0.65rem; {{ !empty($msg['is_read']) ? 'color: #93c5fd;' : 'color: rgba(255,255,255,0.6);' }}"></i>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; color: #94a3b8; font-size: 0.88rem; margin: auto;">
                            <i class="fa-regular fa-paper-plane" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                            <p style="font-weight: 600;">Mulai percakapan langsung dengan {{ $activeEmpName }}</p>
                            <p style="font-size: 0.78rem;">Pesan yang Anda kirim akan diterima secara realtime di aplikasi mobile karyawan.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Chat Input Bar (ALWAYS PROMINENT & VISIBLE) --}}
                <div class="chat-input-wrapper">
                    <form wire:submit.prevent="sendMessage" class="chat-form-box">
                        <input 
                            type="text" 
                            wire:model="newMessage" 
                            placeholder="Tulis pesan untuk {{ $activeEmpName }}... (Tekan Enter untuk kirim)" 
                            class="chat-text-input"
                            autocomplete="off"
                            autofocus
                        >
                        <button type="submit" class="btn-send-message" wire:loading.attr="disabled" wire:target="sendMessage">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            @else
                {{-- Empty State when no conversation selected --}}
                <div class="chat-empty-state">
                    <div class="chat-empty-icon">
                        <i class="fa-regular fa-comments"></i>
                    </div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 0.35rem;">Pilih Percakapan</h3>
                    <p style="font-size: 0.85rem; max-width: 360px; margin-bottom: 1.25rem;">
                        Pilih salah satu karyawan di bilah kiri atau klik tombol di bawah untuk memulai percakapan baru.
                    </p>
                    <button type="button" class="btn-new-chat" style="padding: 0.6rem 1.25rem; font-size: 0.88rem;" wire:click="openNewChatModal">
                        <i class="fa-solid fa-plus"></i> Mulai Chat Baru
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL MULAI CHAT BARU --}}
    @if($showNewChatModal)
        <div class="new-chat-modal-backdrop" wire:click.self="closeNewChatModal">
            <div class="new-chat-modal-card">
                <div class="modal-card-header">
                    <h4 style="font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0;">Mulai Chat Karyawan</h4>
                    <button type="button" wire:click="closeNewChatModal" style="background: none; border: none; font-size: 1.1rem; color: #94a3b8; cursor: pointer;">
                        &times;
                    </button>
                </div>
                <div style="padding: 0.85rem 1rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                    <input type="text" wire:model.live.debounce.300ms="newChatSearch" placeholder="🔍 Cari nama karyawan atau NIK..." class="chat-search-input" autofocus>
                </div>
                <div class="modal-emp-list">
                    @forelse($availableEmployees as $emp)
                        <div class="modal-emp-item" wire:click="startConversationWithEmployee({{ $emp->id }})">
                            <div class="conv-avatar" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                {{ strtoupper(substr($emp->full_name ?? $emp->name, 0, 1)) }}
                            </div>
                            <div style="flex: 1 1 0%; min-width: 0;">
                                <div style="font-weight: 700; font-size: 0.85rem; color: #0f172a;">{{ $emp->full_name ?? $emp->name }}</div>
                                <div style="font-size: 0.72rem; color: #64748b;">
                                    {{ $emp->position?->name ?? 'SPG/MD' }} &bull; {{ $emp->branch?->name ?? 'Cabang' }} (No: {{ $emp->employee_no ?? '-' }})
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; color: #94a3b8;"></i>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 2rem 1rem; color: #94a3b8; font-size: 0.85rem;">
                            Tidak ada karyawan ditemukan.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
