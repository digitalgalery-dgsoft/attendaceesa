<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-[70vh] bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm" x-data="{
        scrollToBottom() {
            let container = $refs.chatContainer;
            if(container) {
                container.scrollTop = container.scrollHeight;
            }
        }
    }" x-init="
        $wire.on('scroll-to-bottom', () => {
            setTimeout(scrollToBottom, 100);
        });
        $wire.on('notify', (message) => {
            new FilamentNotification()
                .title(message)
                .success()
                .send();
        });
    ">
        
        {{-- Sidebar: List of Conversations --}}
        <div class="col-span-1 border-r border-gray-200 dark:border-gray-800 flex flex-col h-full bg-gray-50 dark:bg-gray-800/50">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <h3 class="text-lg font-bold">Percakapan</h3>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2">
                @forelse($conversations as $conversation)
                    @php
                        $latest = $conversation->messages->first();
                        $unread = $conversation->unreadMessagesCount();
                        $isActive = $activeConversationId === $conversation->id;
                    @endphp
                    <div wire:click="selectConversation({{ $conversation->id }})" 
                         class="cursor-pointer p-3 rounded-lg flex items-center justify-between transition {{ $isActive ? 'bg-primary-50 dark:bg-primary-500/10 border border-primary-200 dark:border-primary-500/30' : 'hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <div class="flex items-center space-x-3 truncate">
                            <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold flex-shrink-0">
                                {{ strtoupper(substr($conversation->employee->first_name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="truncate">
                                <h4 class="font-semibold text-sm truncate {{ $isActive ? 'text-primary-600 dark:text-primary-400' : '' }}">
                                    {{ $conversation->employee->first_name ?? 'Unknown' }} {{ $conversation->employee->last_name ?? '' }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $latest ? $latest->message : 'Belum ada pesan' }}
                                </p>
                            </div>
                        </div>
                        @if($unread > 0)
                            <div class="bg-danger-500 text-white text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0">
                                {{ $unread }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center p-4 text-gray-500 text-sm">
                        Belum ada percakapan
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Main Chat Area --}}
        <div class="col-span-1 md:col-span-2 flex flex-col h-full bg-gray-100 dark:bg-gray-900/50">
            @if($activeConversation)
                {{-- Header --}}
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex items-center space-x-3 shadow-sm z-10">
                    <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr($activeConversation->employee->first_name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="font-bold">{{ $activeConversation->employee->first_name ?? 'Unknown' }} {{ $activeConversation->employee->last_name ?? '' }}</h3>
                        <p class="text-xs text-gray-500">{{ $activeConversation->employee->position ?? 'Employee' }}</p>
                    </div>
                </div>

                {{-- Messages List --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="chatContainer">
                    @forelse($messages as $msg)
                        @php
                            $isAdmin = $msg['sender_type'] === 'admin';
                        @endphp
                        <div class="flex {{ $isAdmin ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[75%] rounded-2xl px-4 py-2 shadow-sm {{ $isAdmin ? 'bg-primary-600 text-white rounded-tr-none' : 'bg-white dark:bg-gray-800 rounded-tl-none border border-gray-200 dark:border-gray-700' }}">
                                <p class="text-sm whitespace-pre-wrap">{{ $msg['message'] }}</p>
                                <div class="text-[10px] mt-1 text-right {{ $isAdmin ? 'text-primary-100' : 'text-gray-400' }} flex items-center justify-end space-x-1">
                                    <span>{{ \Carbon\Carbon::parse($msg['created_at'])->format('H:i') }}</span>
                                    @if($isAdmin)
                                        <svg class="w-3 h-3 {{ $msg['is_read'] ? 'text-info-300' : 'text-primary-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 text-sm mt-10">
                            Mulai obrolan dengan {{ $activeConversation->employee->first_name ?? 'karyawan' }}
                        </div>
                    @endforelse
                </div>

                {{-- Input Area --}}
                <div class="p-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
                    <form wire:submit.prevent="sendMessage" class="flex items-center space-x-2">
                        <input type="text" wire:model="newMessage" placeholder="Ketik pesan..." class="flex-1 rounded-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-primary-500 focus:border-primary-500">
                        <button type="submit" class="bg-primary-600 hover:bg-primary-500 text-white rounded-full p-2.5 transition disabled:opacity-50" wire:loading.attr="disabled" wire:target="sendMessage">
                            <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400">
                    <svg class="w-20 h-20 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                    <p class="text-lg font-medium">Pilih percakapan untuk mulai chat</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
