import os
import sys
import json
import glob
import time
from datetime import datetime, date
from http.server import ThreadingHTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs

PORT = 8765
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_FILE = os.path.join(BASE_DIR, 'config.json')
BRAIN_DIR = r'C:\Users\jamil\.gemini\antigravity-ide\brain'

def load_config():
    default_config = {
        "quota_limit": 1000000,
        "warning_percent": 20,
        "danger_percent": 10,
        "active_account_name": "Akun Utama",
        "baseline_tokens": 0,
        "accounts_history": []
    }
    if os.path.exists(CONFIG_FILE):
        try:
            with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
                data = json.load(f)
                default_config.update(data)
                return default_config
        except Exception:
            pass
    return default_config

def save_config(cfg):
    try:
        with open(CONFIG_FILE, 'w', encoding='utf-8') as f:
            json.dump(cfg, f, indent=2)
    except Exception as e:
        print(f"Error saving config: {e}")

def get_session_stats():
    cfg = load_config()
    transcripts = glob.glob(os.path.join(BRAIN_DIR, '*', '.system_generated', 'logs', 'transcript.jsonl'))
    
    if not transcripts:
        return {
            "error": "No transcripts found in brain directory",
            "brain_dir": BRAIN_DIR
        }

    # Sort by modification time to find the active session
    sorted_transcripts = sorted(transcripts, key=os.path.getmtime, reverse=True)
    latest_file = sorted_transcripts[0]
    conv_id = os.path.basename(os.path.dirname(os.path.dirname(os.path.dirname(latest_file))))

    step_count = 0
    user_prompts = 0
    model_responses = 0
    tool_counts = {}
    recent_actions = []
    model_name = "Gemini 3.8 Flash (High)"
    total_chars = 0
    last_prompt = ""

    try:
        with open(latest_file, 'r', encoding='utf-8', errors='ignore') as f:
            for line in f:
                step_count += 1
                total_chars += len(line)
                try:
                    d = json.loads(line)
                    stype = d.get('type', '')
                    if stype == 'USER_INPUT':
                        user_prompts += 1
                        content = d.get('content', '')
                        if '<USER_REQUEST>' in content:
                            req_part = content.split('<USER_REQUEST>')[1].split('</USER_REQUEST>')[0].strip()
                            if req_part:
                                last_prompt = req_part[:120] + ('...' if len(req_part) > 120 else '')
                        if 'Model Selection' in content:
                            for part in content.split('\n'):
                                if 'Model Selection' in part:
                                    clean_m = part.replace('The user changed setting `Model Selection` from None to ', '')
                                    clean_m = clean_m.replace('. No need to comment on this change if the user doesn\'t ask about it. If reporting what model you are, please use a human readable name instead of the exact string.', '').strip()
                                    if clean_m:
                                        model_name = clean_m

                    elif stype == 'PLANNER_RESPONSE':
                        model_responses += 1
                        for tc in d.get('tool_calls', []):
                            name = tc.get('name', 'unknown')
                            tool_counts[name] = tool_counts.get(name, 0) + 1
                            args = tc.get('args', {})
                            summary = args.get('toolSummary') or args.get('toolAction') or args.get('CommandLine') or args.get('TargetFile') or name
                            if isinstance(summary, str) and len(summary) > 80:
                                summary = summary[:77] + '...'
                            recent_actions.append({
                                'step': step_count,
                                'time': d.get('created_at', ''),
                                'tool': name,
                                'summary': str(summary),
                                'status': d.get('status', 'DONE')
                            })
                except Exception:
                    pass
    except Exception as e:
        print(f"Error reading transcript: {e}")

    total_est_tokens = total_chars // 4
    baseline = cfg.get("baseline_tokens", 0)
    account_used_tokens = max(0, total_est_tokens - baseline)

    quota_limit = max(1000, cfg.get("quota_limit", 1000000))
    remaining_tokens = max(0, quota_limit - account_used_tokens)
    used_percent = min(100.0, round((account_used_tokens / quota_limit) * 100, 1))
    remaining_percent = max(0.0, round(100.0 - used_percent, 1))

    danger_thresh = cfg.get("danger_percent", 10)
    warning_thresh = cfg.get("warning_percent", 20)

    if remaining_percent <= danger_thresh:
        status = "DANGER"
    elif remaining_percent <= warning_thresh:
        status = "WARNING"
    else:
        status = "HEALTHY"

    # Today stats
    today_str = date.today().strftime('%Y-%m-%d')
    today_files = [p for p in sorted_transcripts if datetime.fromtimestamp(os.path.getmtime(p)).strftime('%Y-%m-%d') == today_str]
    today_tokens = sum(os.path.getsize(p) for p in today_files) // 4
    all_time_tokens = sum(os.path.getsize(p) for p in sorted_transcripts) // 4

    recent_sessions_summary = []
    for p in sorted_transcripts[:6]:
        sid = os.path.basename(os.path.dirname(os.path.dirname(os.path.dirname(p))))
        mtime = datetime.fromtimestamp(os.path.getmtime(p)).strftime('%Y-%m-%d %H:%M')
        sz_kb = round(os.path.getsize(p) / 1024, 1)
        recent_sessions_summary.append({
            "id": sid,
            "modified": mtime,
            "size_kb": sz_kb,
            "est_tokens": int(sz_kb * 1024 // 4),
            "is_current": (sid == conv_id)
        })

    return {
        "timestamp": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        "active_session": {
            "conversation_id": conv_id,
            "model_name": model_name,
            "total_steps": step_count,
            "user_prompts": user_prompts,
            "model_responses": model_responses,
            "total_tokens_session": total_est_tokens,
            "last_prompt": last_prompt,
            "recent_actions": list(reversed(recent_actions[-15:])),
            "tool_counts": tool_counts
        },
        "quota": {
            "quota_limit": quota_limit,
            "account_used_tokens": account_used_tokens,
            "remaining_tokens": remaining_tokens,
            "used_percent": used_percent,
            "remaining_percent": remaining_percent,
            "status": status,
            "warning_percent": warning_thresh,
            "danger_percent": danger_thresh,
            "active_account_name": cfg.get("active_account_name", "Akun Utama"),
            "baseline_tokens": baseline
        },
        "overview": {
            "today_tokens": today_tokens,
            "today_sessions_count": len(today_files),
            "all_time_tokens": all_time_tokens,
            "total_sessions_count": len(sorted_transcripts),
            "recent_sessions": recent_sessions_summary
        },
        "accounts_history": cfg.get("accounts_history", [])
    }

class MonitorHandler(BaseHTTPRequestHandler):
    def _send_json(self, data, status=200):
        self.send_response(status)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
        self.end_headers()
        self.wfile.write(json.dumps(data, ensure_ascii=False).encode('utf-8'))

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path

        if path == '/api/stats':
            data = get_session_stats()
            self._send_json(data)
        elif path == '/api/config':
            cfg = load_config()
            self._send_json(cfg)
        elif path == '/' or path == '/index.html':
            html_file = os.path.join(BASE_DIR, 'index.html')
            if os.path.exists(html_file):
                self.send_response(200)
                self.send_header('Content-Type', 'text/html; charset=utf-8')
                self.send_header('Cache-Control', 'no-cache')
                self.end_headers()
                with open(html_file, 'rb') as f:
                    self.wfile.write(f.read())
            else:
                self.send_response(404)
                self.end_headers()
                self.wfile.write(b"index.html not found")
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b"Not Found")

    def do_POST(self):
        parsed = urlparse(self.path)
        path = parsed.path
        content_length = int(self.headers.get('Content-Length', 0))
        body = self.rfile.read(content_length)

        try:
            payload = json.loads(body.decode('utf-8')) if body else {}
        except Exception:
            payload = {}

        if path == '/api/config':
            cfg = load_config()
            if 'quota_limit' in payload:
                cfg['quota_limit'] = int(payload['quota_limit'])
            if 'active_account_name' in payload:
                cfg['active_account_name'] = str(payload['active_account_name']).strip()
            if 'warning_percent' in payload:
                cfg['warning_percent'] = int(payload['warning_percent'])
            if 'danger_percent' in payload:
                cfg['danger_percent'] = int(payload['danger_percent'])
            save_config(cfg)
            self._send_json({"success": True, "config": cfg})

        elif path == '/api/switch-account':
            cfg = load_config()
            stats = get_session_stats()
            current_total = stats.get('active_session', {}).get('total_tokens_session', 0)
            
            old_account = cfg.get("active_account_name", "Akun Sebelumnya")
            new_account = payload.get("new_account_name", f"Akun Baru ({datetime.now().strftime('%H:%M')})")
            
            # Log history
            if 'accounts_history' not in cfg:
                cfg['accounts_history'] = []
            
            cfg['accounts_history'].insert(0, {
                "name": old_account,
                "switched_at": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                "tokens_consumed": current_total - cfg.get("baseline_tokens", 0)
            })

            # Update new active account & baseline
            cfg['active_account_name'] = new_account
            cfg['baseline_tokens'] = current_total
            save_config(cfg)

            self._send_json({
                "success": True, 
                "message": f"Berhasil switch ke {new_account}. Counter kuota di-reset untuk akun baru.",
                "config": cfg
            })

        elif path == '/api/reset-baseline':
            cfg = load_config()
            stats = get_session_stats()
            current_total = stats.get('active_session', {}).get('total_tokens_session', 0)
            cfg['baseline_tokens'] = current_total
            save_config(cfg)
            self._send_json({"success": True, "baseline_tokens": current_total})

        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b"Endpoint not found")

    def log_message(self, format, *args):
        # Silence routine terminal access logs for ultra-clean output
        return

def run():
    server_address = ('127.0.0.1', PORT)
    httpd = ThreadingHTTPServer(server_address, MonitorHandler)
    print(f"============================================================")
    print(f"  ANTIGRAVITY AI TOKEN & QUOTA MONITOR (REALTIME)           ")
    print(f"============================================================")
    print(f"Server berjalan di: http://127.0.0.1:{PORT}")
    print(f"Dashboard siap dibuka di browser...")
    print(f"Tekan Ctrl+C untuk menghentikan server.")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nServer dihentikan.")
        httpd.server_close()

if __name__ == '__main__':
    run()
