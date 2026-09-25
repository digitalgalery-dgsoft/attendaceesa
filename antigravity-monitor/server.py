import os
import sys
import json
import glob
import re
import time
import sqlite3
import base64
from datetime import datetime, date
from http.server import ThreadingHTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs

PORT = 8765
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_FILE = os.path.join(BASE_DIR, 'config.json')
# Dynamic brain directory based on current logged in OS user
BRAIN_DIR = os.environ.get('ANTIGRAVITY_BRAIN_DIR') or os.path.expanduser(os.path.join('~', '.gemini', 'antigravity-ide', 'brain'))

def get_ide_account_profile():
    roaming = os.environ.get('APPDATA', '')
    candidates = [
        os.path.join(roaming, 'Antigravity IDE', 'User', 'globalStorage', 'state.vscdb'),
        os.path.join(roaming, 'Antigravity', 'User', 'globalStorage', 'state.vscdb')
    ]
    db_file = None
    for c in candidates:
        if os.path.exists(c):
            db_file = c
            break

    if not db_file:
        return None

    try:
        conn = sqlite3.connect(f'file:{db_file}?mode=ro', uri=True)
        cur = conn.cursor()
        cur.execute("SELECT value FROM ItemTable WHERE key=?", ("antigravityUnifiedStateSync.userStatus",))
        row = cur.fetchone()
        conn.close()

        if not row or not row[0]:
            return None

        # Level 1: Outer base64 decode
        outer_bytes = base64.b64decode(row[0])

        extracted = {
            "email": None,
            "name": None,
            "plan_name": "Google AI Free (Standard)",
            "tier_id": "FREE",
            "is_pro": False,
            "avatar_url": None,
            "detection_source": "IDE Account State (state.vscdb)"
        }

        # Level 2: Search for inner base64 chunks
        b64_pattern = re.compile(rb'[A-Za-z0-9+/=]{16,}')
        chunks = b64_pattern.findall(outer_bytes)

        all_decoded_chunks = []
        for ch in chunks:
            pad = len(ch) % 4
            ch_padded = ch + (b'=' * (4 - pad) if pad != 0 else b'')
            try:
                dec = base64.b64decode(ch_padded)
                all_decoded_chunks.append(dec)
            except Exception:
                pass

        combined_search = b' '.join([outer_bytes] + all_decoded_chunks)

        # 1. Tier & Plan Detection
        if b'g1-pro-tier' in combined_search or b'Google AI Pro' in combined_search:
            extracted["is_pro"] = True
            extracted["tier_id"] = "PRO"
            extracted["plan_name"] = "Google AI Pro (Gemini Pro / Google One AI Premium)"
        elif b'Google AI Ultra' in combined_search:
            extracted["is_pro"] = True
            extracted["tier_id"] = "PRO"
            extracted["plan_name"] = "Google AI Ultra (Enterprise / Premium)"
        elif b'free' in combined_search.lower():
            extracted["is_pro"] = False
            extracted["tier_id"] = "FREE"
            extracted["plan_name"] = "Google AI Free (Standard)"

        # 2. Email Detection
        email_match = re.search(rb'[\w\.-]+@[\w\.-]+\.\w+', combined_search)
        if email_match:
            extracted["email"] = email_match.group(0).decode('utf-8', errors='ignore')

        # 3. Avatar URL
        avatar_match = re.search(rb'https://lh3\.googleusercontent\.com/[^\s\x00-\x1f\x7f-\xff"\']+', combined_search)
        if avatar_match:
            extracted["avatar_url"] = avatar_match.group(0).decode('utf-8', errors='ignore')

        # 4. Display Name
        for dec in all_decoded_chunks:
            if extracted["email"] and extracted["email"].encode() in dec:
                match = re.search(rb'([A-Za-z0-9\s]{2,30}):[\x00-\x20]*' + re.escape(extracted["email"].encode()), dec)
                if match:
                    extracted["name"] = match.group(1).decode('utf-8', errors='ignore').strip()
            if not extracted["name"]:
                m = re.search(rb'\x01Gg([A-Za-z0-9\s]{2,30})', dec)
                if m:
                    extracted["name"] = m.group(1).decode('utf-8', errors='ignore').strip()

        if not extracted["name"] and extracted["email"]:
            extracted["name"] = extracted["email"].split('@')[0]

        return extracted
    except Exception as e:
        return {"error": str(e)}

def get_local_ip():
    try:
        import socket
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(('8.8.8.8', 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        try:
            import socket
            return socket.gethostbyname(socket.gethostname())
        except Exception:
            return '127.0.0.1'

MODEL_PROFILES = {
    "gemini 3.8 flash": {"name": "Gemini 3.8 Flash (High)", "family": "gemini", "tier": "PRO/FREE", "pro_quota": 10000000, "free_quota": 1000000, "weight": 1.0, "icon": "fa-bolt-lightning", "color": "#10b981", "badge": "Flash High Speed"},
    "gemini 3.7 flash": {"name": "Gemini 3.7 Flash Medium", "family": "gemini", "tier": "PRO/FREE", "pro_quota": 10000000, "free_quota": 1000000, "weight": 1.0, "icon": "fa-bolt-lightning", "color": "#10b981", "badge": "Flash Medium"},
    "gemini 3.6 flash": {"name": "Gemini 3.6 Flash Medium", "family": "gemini", "tier": "PRO/FREE", "pro_quota": 8000000, "free_quota": 1000000, "weight": 1.0, "icon": "fa-bolt-lightning", "color": "#10b981", "badge": "Flash Standard"},
    "gemini 3.1 pro":   {"name": "Gemini 3.1 Pro Low", "family": "gemini", "tier": "PRO", "pro_quota": 3000000, "free_quota": 500000, "weight": 1.5, "icon": "fa-brain", "color": "#818cf8", "badge": "Deep Reasoning (Pro)"},
    "claude sonnet":    {"name": "Claude Sonnet 4.6 (Thinking)", "family": "claude", "tier": "PRO", "pro_quota": 1500000, "free_quota": 0, "weight": 2.0, "icon": "fa-wand-magic-sparkles", "color": "#f59e0b", "badge": "Anthropic Thinking (Pro Only)"},
    "claude opus":      {"name": "Claude Opus 4.6 (Thinking)", "family": "claude", "tier": "PRO", "pro_quota": 1000000, "free_quota": 0, "weight": 3.0, "icon": "fa-gem", "color": "#ec4899", "badge": "Anthropic Flagship (Pro Only)"}
}

def resolve_model_profile(m_name):
    low = (m_name or '').lower()
    for key, prof in MODEL_PROFILES.items():
        if key in low:
            return prof
    if 'opus' in low:
        return MODEL_PROFILES["claude opus"]
    if 'sonnet' in low or 'claude' in low:
        return MODEL_PROFILES["claude sonnet"]
    if 'pro' in low:
        return MODEL_PROFILES["gemini 3.1 pro"]
    if 'flash' in low:
        return MODEL_PROFILES["gemini 3.8 flash"]
    return {
        "name": m_name or "Gemini Model", "family": "gemini", "tier": "FREE",
        "pro_quota": 5000000, "free_quota": 1000000, "weight": 1.0,
        "icon": "fa-microchip", "color": "#94a3b8", "badge": "Standard AI"
    }

def load_config():
    default_config = {
        "quota_limit": 10000000,
        "auto_model_quota": True,
        "account_tier": "AUTO",
        "warning_percent": 20,
        "danger_percent": 10,
        "active_account_name": "Akun Gemini Pro",
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
    current_model = "Gemini 3.8 Flash (High)"
    model_tokens = {}
    total_chars = 0
    last_prompt = ""

    try:
        with open(latest_file, 'r', encoding='utf-8', errors='ignore') as f:
            for line in f:
                step_count += 1
                total_chars += len(line)
                char_tokens = len(line) // 4
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
                            m_match = re.search(r"The user changed setting `Model Selection` from .*? to (.*?)(?:\.\s*No need to comment|\n|</USER_SETTINGS_CHANGE>|\.$)", content)
                            if m_match:
                                current_model = m_match.group(1).strip()

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

                model_tokens[current_model] = model_tokens.get(current_model, 0) + char_tokens
    except Exception as e:
        print(f"Error reading transcript: {e}")

    model_name = current_model
    profile = resolve_model_profile(model_name)
    total_est_tokens = total_chars // 4
    baseline = cfg.get("baseline_tokens", 0)
    account_used_tokens = max(0, total_est_tokens - baseline)

    # 1. Tier & Account Detection:
    ide_profile = get_ide_account_profile()
    is_pro_features_used = ('claude' in model_name.lower()) or ('pro' in model_name.lower()) or (total_est_tokens > 1200000)
    
    if ide_profile and "is_pro" in ide_profile:
        detected_tier = ide_profile["tier_id"]
        is_pro_detected = ide_profile["is_pro"]
        tier_reason = ide_profile.get("plan_name", "Google AI Pro")
    else:
        detected_tier = "PRO" if is_pro_features_used else "FREE"
        is_pro_detected = (detected_tier == "PRO")
        tier_reason = "Model Pro/Claude aktif" if is_pro_detected else "Akun Standar Free"

    configured_tier = cfg.get("account_tier", "AUTO")
    if configured_tier != "AUTO":
        active_tier = configured_tier
        is_pro = (active_tier == "PRO")
        tier_reason = f"Mode: {active_tier} TIER"
    elif ide_profile and "is_pro" in ide_profile and (not cfg.get("active_account_name") or "rayzen" in cfg.get("active_account_name", "").lower()):
        active_tier = ide_profile["tier_id"]
        is_pro = ide_profile["is_pro"]
        tier_reason = ide_profile.get("plan_name", "Google AI Pro")
    else:
        active_tier = "FREE"
        is_pro = False
        tier_reason = "Akun Standar Free"

    # 2. Dynamic Quota based on Model and Tier:
    auto_model_quota = cfg.get("auto_model_quota", True)
    if auto_model_quota:
        if is_pro:
            quota_limit = profile["pro_quota"]
        else:
            quota_limit = profile.get("free_quota", 1000000)
    else:
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

    # Dynamic Account Label (Strictly honor user config)
    active_account_label = cfg.get("active_account_name", "")
    if not active_account_label:
        if ide_profile and ide_profile.get("email"):
            active_account_label = f"{ide_profile.get('name', 'User')} ({ide_profile.get('email')})"
        else:
            active_account_label = "Jei Design (Google Auth)"

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
        "model_info": {
            "name": model_name,
            "clean_name": profile["name"],
            "family": profile["family"],
            "icon": profile["icon"],
            "color": profile["color"],
            "badge": profile["badge"],
            "weight": profile["weight"],
            "pro_quota": profile["pro_quota"],
            "free_quota": profile["free_quota"],
            "model_tokens": model_tokens
        },
        "account_profile": ide_profile,
        "tier_info": {
            "detected_tier": detected_tier,
            "active_tier": active_tier,
            "is_pro": is_pro,
            "tier_badge": "PRO PLAN (Gemini Pro / Google One AI)" if is_pro else "FREE PLAN (Standard Google Account)",
            "detection_reason": tier_reason,
            "auto_model_quota": auto_model_quota,
            "user_email": ide_profile.get("email") if ide_profile else None,
            "user_name": ide_profile.get("name") if ide_profile else None,
            "avatar_url": ide_profile.get("avatar_url") if ide_profile else None,
            "detection_source": ide_profile.get("detection_source") if ide_profile else "Heuristik Model"
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
            "active_account_name": active_account_label,
            "baseline_tokens": baseline,
            "auto_model_quota": auto_model_quota
        },
        "overview": {
            "today_tokens": today_tokens,
            "today_sessions_count": len(today_files),
            "all_time_tokens": all_time_tokens,
            "total_sessions_count": len(sorted_transcripts),
            "recent_sessions": recent_sessions_summary
        },
        "accounts_history": cfg.get("accounts_history", []),
        "network": {
            "local_ip": get_local_ip(),
            "port": PORT,
            "access_url": f"http://{get_local_ip()}:{PORT}"
        }
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
            if 'auto_model_quota' in payload:
                cfg['auto_model_quota'] = bool(payload['auto_model_quota'])
            if 'account_tier' in payload:
                cfg['account_tier'] = str(payload['account_tier']).strip()
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
            new_tier = payload.get("account_tier", "FREE")
            
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
            cfg['account_tier'] = new_tier
            cfg['baseline_tokens'] = current_total
            save_config(cfg)

            self._send_json({
                "success": True, 
                "message": f"Berhasil switch ke {new_account} ({new_tier} Tier). Counter kuota di-reset untuk akun baru.",
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
    server_address = ('0.0.0.0', PORT)
    httpd = ThreadingHTTPServer(server_address, MonitorHandler)
    local_ip = get_local_ip()
    print(f"============================================================")
    print(f"  ANTIGRAVITY AI TOKEN & QUOTA MONITOR (REALTIME)           ")
    print(f"============================================================")
    print(f"Server Lokal       : http://localhost:{PORT} atau http://127.0.0.1:{PORT}")
    print(f"Akses Device Lain  : http://{local_ip}:{PORT} (HP/Tablet/Laptop satu WiFi)")
    print(f"Brain Directory    : {BRAIN_DIR}")
    print(f"============================================================")
    print(f"Dashboard siap dibuka di browser...")
    print(f"Tekan Ctrl+C untuk menghentikan server.")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nServer dihentikan.")
        httpd.server_close()

if __name__ == '__main__':
    run()
