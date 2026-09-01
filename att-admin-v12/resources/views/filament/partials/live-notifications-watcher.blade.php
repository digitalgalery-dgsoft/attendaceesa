<script>
(function() {
    // Watcher for incoming chat messages & helpdesk tickets across the entire admin panel
    let lastNotifiedMsgId = parseInt(sessionStorage.getItem('esa_last_notified_msg_id') || '0', 10);
    let isChecking = false;

    function playNotificationChime() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            if (ctx.state === 'suspended') {
                ctx.resume();
            }
            const now = ctx.currentTime;
            
            // Pleasant modern 2-tone chime
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, now); // D5
            osc.frequency.exponentialRampToValueAtTime(880, now + 0.12); // A5
            
            gain.gain.setValueAtTime(0.25, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
            
            osc.connect(gain);
            gain.connect(ctx.destination);
            
            osc.start(now);
            osc.stop(now + 0.5);
        } catch (e) {
            // Audio context restricted until first user interaction
        }
    }

    function checkLiveChatNotifications() {
        if (isChecking) return;
        isChecking = true;

        fetch('/admin/unread-helpdesk-chat', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            isChecking = false;
            if (!data || !data.latest_message) return;

            const msg = data.latest_message;
            const msgId = parseInt(msg.id, 10);

            // On first page load, sync lastNotifiedMsgId if not yet set
            if (lastNotifiedMsgId === 0) {
                lastNotifiedMsgId = msgId;
                sessionStorage.setItem('esa_last_notified_msg_id', String(msgId));
                return;
            }

            // If a brand new message has arrived from an employee
            if (msgId > lastNotifiedMsgId) {
                lastNotifiedMsgId = msgId;
                sessionStorage.setItem('esa_last_notified_msg_id', String(msgId));

                // 1. Play sound chime
                playNotificationChime();

                // 2. Trigger Filament Toast Notification
                if (typeof FilamentNotification !== 'undefined') {
                    const toast = new FilamentNotification()
                        .title(msg.is_ticket ? '🎫 ' + msg.employee_name : '💬 ' + msg.employee_name)
                        .body(msg.message)
                        .icon('heroicon-o-chat-bubble-left-right')
                        .duration(10000);

                    if (msg.is_ticket) {
                        toast.warning();
                    } else {
                        toast.success();
                    }

                    if (typeof FilamentNotificationAction !== 'undefined') {
                        toast.actions([
                            new FilamentNotificationAction('open')
                                .label('Buka Live Chat')
                                .url('/admin/live-chat')
                                .button()
                        ]);
                    }

                    toast.send();
                } else {
                    // Fallback browser custom event for notifications
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            id: 'chat_' + msgId,
                            title: (msg.is_ticket ? '🎫 ' : '💬 ') + msg.employee_name,
                            body: msg.message,
                            status: msg.is_ticket ? 'warning' : 'success',
                            duration: 10000
                        }
                    }));
                }

                // 3. Update topbar notification bell & counter
                if (window.Livewire) {
                    window.Livewire.dispatch('databaseNotificationsSent');
                }

                // 4. If currently on Live Chat page, refresh conversation list & active messages
                if (window.location.pathname.includes('/admin/live-chat') && window.Livewire) {
                    window.Livewire.dispatch('refreshChatList');
                    window.Livewire.dispatch('pollMessages');
                }
            }
        })
        .catch(err => {
            isChecking = false;
        });
    }

    // Poll every 4 seconds for immediate responsiveness
    setInterval(checkLiveChatNotifications, 4000);
    // Initial check after 2 seconds
    setTimeout(checkLiveChatNotifications, 2000);
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .swal2-esa-popup {
        font-family: inherit !important;
        border-radius: 18px !important;
        padding: 1.5rem !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        border: 1px solid #e2e8f0 !important;
    }
    .dark .swal2-esa-popup {
        background: #1e293b !important;
        color: #f1f5f9 !important;
        border-color: #334155 !important;
    }
    .swal2-esa-btn-confirm {
        border-radius: 10px !important;
        font-weight: 700 !important;
        font-size: 0.88rem !important;
        padding: 0.6rem 1.25rem !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.2s ease !important;
    }
    .swal2-esa-btn-cancel {
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        padding: 0.6rem 1.25rem !important;
        transition: all 0.2s ease !important;
    }
</style>
