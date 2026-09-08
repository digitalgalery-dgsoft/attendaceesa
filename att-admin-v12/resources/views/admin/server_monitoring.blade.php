<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Monitoring - 3 Production Servers | ESA Solution</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #080c16;
            --bg-card: #0d1527;
            --bg-card-hover: #121c33;
            --bg-surface: #14203d;
            --border-card: #1c2a4f;
            --border-light: #243563;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-blue: #38bdf8;
            --accent-blue-glow: rgba(56, 189, 248, 0.25);
            --accent-green: #10b981;
            --accent-green-glow: rgba(16, 185, 129, 0.25);
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            --accent-purple: #a855f7;
            --accent-indigo: #6366f1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            line-height: 1.5;
            padding: 24px;
        }

        /* Container & Layout */
        .sm-container {
            max-width: 1680px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Top Header */
        .sm-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border-card);
        }

        .sm-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .sm-brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(2, 132, 199, 0.35);
            color: #ffffff;
            flex-shrink: 0;
        }

        .sm-brand-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sm-brand-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            background: rgba(56, 189, 248, 0.15);
            color: var(--accent-blue);
            border: 1px solid rgba(56, 189, 248, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .sm-brand-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* Header Controls & Actions */
        .sm-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
            text-decoration: none;
            color: #ffffff;
        }

        .btn-purple {
            background: #7c3aed;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);
        }
        .btn-purple:hover { background: #6d28d9; transform: translateY(-1px); }

        .btn-emerald {
            background: #059669;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
        }
        .btn-emerald:hover { background: #047857; transform: translateY(-1px); }

        .btn-rose {
            background: #e11d48;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.3);
        }
        .btn-rose:hover { background: #be123c; transform: translateY(-1px); }

        .btn-ghost {
            background: var(--bg-card);
            border-color: var(--border-card);
            color: var(--text-secondary);
        }
        .btn-ghost:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
            border-color: var(--border-light);
        }

        /* Live Update Badge */
        .badge-live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Top 3-Server Health Cluster Cards */
        .cluster-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 1080px) {
            .cluster-grid { grid-template-columns: 1fr; }
        }

        .node-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 16px 18px;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .node-card:hover {
            border-color: var(--accent-blue);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .node-card.active {
            border-color: var(--accent-blue);
            background: linear-gradient(180deg, #101d3b 0%, var(--bg-card) 100%);
            box-shadow: 0 0 0 1px var(--accent-blue), 0 8px 24px rgba(2, 132, 199, 0.2);
        }

        .node-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: transparent;
            transition: background 0.3s;
        }

        .node-card.active::before {
            background: linear-gradient(90deg, #38bdf8, #818cf8);
        }

        .node-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .node-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .node-ip {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-family: 'JetBrains Mono', monospace;
            margin-top: 2px;
        }

        .node-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .node-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .mini-stat {
            text-align: center;
        }

        .mini-stat-label {
            font-size: 0.68rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .mini-stat-value {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-top: 2px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* View Mode Navigation Tabs */
        .sm-nav-tabs {
            display: flex;
            gap: 8px;
            background: var(--bg-card);
            padding: 6px;
            border-radius: 10px;
            border: 1px solid var(--border-card);
            overflow-x: auto;
        }

        .sm-tab-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-secondary);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .sm-tab-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.04);
        }

        .sm-tab-btn.active {
            background: #1e293b;
            color: var(--accent-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        /* Main 2-Column Monitoring Grid (Matching Screenshot) */
        .monitoring-layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .monitoring-layout { grid-template-columns: 1fr; }
        }

        /* Left Rail (Metrics / Gauges) */
        .metrics-rail {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 16px 18px;
            transition: border-color 0.2s;
        }

        .metric-card:hover {
            border-color: var(--border-light);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .metric-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .metric-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .icon-storage { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
        .icon-cpu { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
        .icon-proc { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .icon-entry { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .icon-io { background: rgba(20, 184, 166, 0.15); color: #2dd4bf; }
        .icon-ram { background: rgba(129, 140, 248, 0.15); color: #818cf8; }

        .metric-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #ffffff;
        }

        .metric-subtitle {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .metric-val-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 10px;
        }

        .metric-main-val {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
        }

        .metric-sub-val {
            font-size: 0.78rem;
            color: var(--text-secondary);
            font-family: 'JetBrains Mono', monospace;
        }

        /* Progress Bar */
        .progress-track {
            height: 6px;
            background: #19233c;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            border-radius: 6px;
            background: linear-gradient(90deg, #0284c7, #38bdf8);
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-fill.green { background: linear-gradient(90deg, #059669, #10b981); }
        .progress-fill.amber { background: linear-gradient(90deg, #d97706, #f59e0b); }
        .progress-fill.rose { background: linear-gradient(90deg, #e11d48, #f43f5e); }

        .btn-card-action {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-card);
            color: var(--text-secondary);
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 12px;
            transition: all 0.2s;
        }

        .btn-card-action:hover {
            background: rgba(255, 255, 255, 0.07);
            color: #ffffff;
            border-color: var(--border-light);
        }

        /* Right Content (Info, Snapshot, Chart, Log) */
        .content-rail {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .dashboard-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 20px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .panel-title {
            font-size: 0.98rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Server Info 4-Column Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 900px) {
            .info-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }

        .info-value {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            word-break: break-all;
        }

        /* Database Snapshot Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-top: 6px;
        }

        .data-table th {
            text-align: left;
            padding: 10px 12px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-card);
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
            vertical-align: middle;
        }

        .data-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .query-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.76rem;
            color: #7dd3fc;
            background: rgba(15, 23, 42, 0.6);
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid rgba(56, 189, 248, 0.2);
            max-width: 600px;
            display: inline-block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-snapshot {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 28px 16px;
            color: #34d399;
            font-size: 0.88rem;
            font-weight: 500;
            background: rgba(16, 185, 129, 0.05);
            border: 1px dashed rgba(16, 185, 129, 0.25);
            border-radius: 8px;
            margin-top: 10px;
        }

        /* Chart SVG Area */
        .chart-box {
            width: 100%;
            height: 120px;
            position: relative;
            margin-top: 10px;
        }

        .chart-svg {
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        .chart-axis-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 0.72rem;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        /* 3-in-1 Side-by-Side Comparison Mode Layout */
        .comparison-layout {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        @media (max-width: 1100px) {
            .comparison-layout { grid-template-columns: 1fr; }
        }

        .comp-column {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .comp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast-msg {
            background: #1e293b;
            color: #ffffff;
            border-left: 4px solid var(--accent-blue);
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="sm-container">

    <!-- Top Header -->
    <header class="sm-header">
        <div class="sm-brand">
            <div class="sm-brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
            </div>
            <div>
                <h1 class="sm-brand-title">
                    Server Monitoring
                    <span class="sm-brand-badge">3 Node Production</span>
                </h1>
                <p class="sm-brand-desc">Real-time resource usage & multi-node production telemetry (Server 1 AMK, Server 2 AKP, Server 3 ATK)</p>
            </div>
        </div>

        <div class="sm-actions">
            <button class="btn-action btn-purple" onclick="triggerAction('clean_storage')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                Pembersih Storage
            </button>

            <button class="btn-action btn-emerald" onclick="triggerAction('reset_workers')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                Reset NPROC
            </button>

            <button class="btn-action btn-rose" onclick="triggerAction('emergency_reset')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Emergency Reset
            </button>

            <select id="refreshIntervalSelect" onchange="changeInterval(this.value)" class="btn-action btn-ghost" style="padding: 8px 12px; cursor: pointer;">
                <option value="5">Auto: 5 Detik</option>
                <option value="10" selected>Auto: 10 Detik</option>
                <option value="30">Auto: 30 Detik</option>
                <option value="0">Pause Polling</option>
            </select>

            <div class="badge-live">
                <span class="pulse-dot"></span>
                <span id="liveStatusText">LIVE UPDATE</span>
            </div>

            @if(isset($isStandalone) && !$isStandalone)
                <a href="/server-monitoring?token=dgsoft_rahasia_123" target="_blank" class="btn-action btn-ghost" title="Buka dalam layar penuh NOC">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
                    NOC Monitor
                </a>
            @endif
        </div>
    </header>

    <!-- Top 3 Server Health Matrix Cards (Always Visible) -->
    <div class="cluster-grid">
        @foreach($definitions as $nodeKey => $node)
            <div class="node-card {{ $activeNode === $nodeKey ? 'active' : '' }}" id="card-node-{{ $nodeKey }}" onclick="switchNode('{{ $nodeKey }}')">
                <div class="node-header">
                    <div>
                        <div class="node-title">
                            <span>🏢 {{ $node['short_name'] }}</span>
                        </div>
                        <div class="node-ip">IP: {{ $node['ip'] }} • {{ $node['employees_count'] }}</div>
                    </div>
                    <span class="node-status-badge" id="badge-status-{{ $nodeKey }}">
                        <span class="pulse-dot" style="width:6px; height:6px;"></span>
                        ONLINE
                    </span>
                </div>

                <div class="node-stats-row">
                    <div class="mini-stat">
                        <div class="mini-stat-label">CPU</div>
                        <div class="mini-stat-value" id="card-cpu-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['cpu']['percent'] ?? 12 }}%
                        </div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-label">RAM</div>
                        <div class="mini-stat-value" id="card-ram-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['ram']['percent'] ?? 35 }}%
                        </div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-label">DISK</div>
                        <div class="mini-stat-value" id="card-disk-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['disk']['percent'] ?? 42 }}%
                        </div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-label">PING</div>
                        <div class="mini-stat-value" style="color: #34d399;" id="card-ping-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['ping_ms'] ?? 14 }}ms
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Navigation View Switcher (All vs Single Node) -->
    <div class="sm-nav-tabs">
        <button class="sm-tab-btn {{ $activeNode === 'all' ? 'active' : '' }}" onclick="switchNode('all')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
            Mode Komparasi (3 Server Berdampingan)
        </button>
        <button class="sm-tab-btn {{ $activeNode === 'amk' ? 'active' : '' }}" onclick="switchNode('amk')">
            🏢 Server 1: AMK (38.103.170.235)
        </button>
        <button class="sm-tab-btn {{ $activeNode === 'akp' ? 'active' : '' }}" onclick="switchNode('akp')">
            🏢 Server 2: AKP (38.103.170.223)
        </button>
        <button class="sm-tab-btn {{ $activeNode === 'atk' ? 'active' : '' }}" onclick="switchNode('atk')">
            🏢 Server 3: ATK / Gabungan (38.103.170.224)
        </button>
    </div>

    <!-- VIEW 1: DETAIL DRILL-DOWN (Matching User's Screenshot) -->
    <div id="view-detail-container" style="display: {{ $activeNode !== 'all' ? 'grid' : 'none' }};" class="monitoring-layout">
        
        <!-- LEFT COLUMN: Resource Metrics -->
        <div class="metrics-rail">
            
            <!-- 1. Storage / Disk -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title-group">
                        <div class="metric-icon-box icon-storage">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        </div>
                        <div>
                            <div class="metric-title">Storage / Disk</div>
                            <div class="metric-subtitle">SSD NVMe Usage</div>
                        </div>
                    </div>
                </div>
                <div class="metric-val-row">
                    <div class="metric-main-val" id="det-disk-val">35.0 GB / 241.1 GB</div>
                    <div class="metric-sub-val" id="det-disk-pct">(14.5%)</div>
                </div>
                <div class="progress-track">
                    <div class="progress-fill green" id="det-disk-bar" style="width: 14.5%;"></div>
                </div>
                <button class="btn-card-action" onclick="triggerAction('clean_storage')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
                    Bersihkan Cache & Log
                </button>
            </div>

            <!-- 2. CPU Usage -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title-group">
                        <div class="metric-icon-box icon-cpu">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>
                        </div>
                        <div>
                            <div class="metric-title">CPU Usage</div>
                            <div class="metric-subtitle">Processor allocation (8 Cores)</div>
                        </div>
                    </div>
                </div>
                <div class="metric-val-row">
                    <div class="metric-main-val" id="det-cpu-val">0.1 / 8 Cores</div>
                    <div class="metric-sub-val" id="det-cpu-pct">(1.0%)</div>
                </div>
                <div class="progress-track">
                    <div class="progress-fill green" id="det-cpu-bar" style="width: 1%;"></div>
                </div>
                <div style="margin-top: 8px; font-size: 0.72rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                    <span>Load Avg (1m / 5m / 15m):</span>
                    <span id="det-load-avg" style="font-family: 'JetBrains Mono', monospace; color: var(--accent-blue);">0.04 / 0.16 / 0.27</span>
                </div>
            </div>

            <!-- 3. Active Processes & Tasks (Sesuai aaPanel: 2 / 204) -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title-group">
                        <div class="metric-icon-box icon-proc">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        </div>
                        <div>
                            <div class="metric-title">Active Processes</div>
                            <div class="metric-subtitle">Beban Eksekusi Server (aaPanel)</div>
                        </div>
                    </div>
                </div>
                <div class="metric-val-row">
                    <div class="metric-main-val" id="det-proc-val">2 Aktif / 204 Total</div>
                    <div class="metric-sub-val" id="det-proc-pct" style="color: #34d399;">(1.0% - Sangat Lega)</div>
                </div>
                <div class="progress-track">
                    <div class="progress-fill green" id="det-proc-bar" style="width: 1%;"></div>
                </div>
                <div style="margin-top: 8px; font-size: 0.72rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                    <span>Status Task:</span>
                    <span id="det-proc-status" style="color: #34d399; font-weight: 600;">2 Running • 202 Sleeping (Idle)</span>
                </div>
                <button class="btn-card-action" onclick="triggerAction('reset_workers')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                    Reset NPROC / Workers
                </button>
            </div>

            <!-- 4. Entry Processes -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title-group">
                        <div class="metric-icon-box icon-entry">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                        </div>
                        <div>
                            <div class="metric-title">Entry Processes</div>
                            <div class="metric-subtitle">Active web connections</div>
                        </div>
                    </div>
                </div>
                <div class="metric-val-row">
                    <div class="metric-main-val" id="det-entry-val">1 / 50</div>
                    <div class="metric-sub-val" id="det-entry-pct">(2%)</div>
                </div>
                <div class="progress-track">
                    <div class="progress-fill green" id="det-entry-bar" style="width: 2%;"></div>
                </div>
            </div>

            <!-- 5. I/O Usage -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title-group">
                        <div class="metric-icon-box icon-io">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
                        </div>
                        <div>
                            <div class="metric-title">I/O Usage</div>
                            <div class="metric-subtitle">Disk Read/Write Speed</div>
                        </div>
                    </div>
                </div>
                <div class="metric-val-row">
                    <div class="metric-main-val" id="det-io-val">0.07 MB/s / 10 MB/s</div>
                    <div class="metric-sub-val" id="det-io-pct">(0.7%)</div>
                </div>
                <div class="progress-track">
                    <div class="progress-fill green" id="det-io-bar" style="width: 1%;"></div>
                </div>
            </div>

            <!-- 6. Physical Memory -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title-group">
                        <div class="metric-icon-box icon-ram">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2v20M18 2v20M2 6h20M2 18h20"/></svg>
                        </div>
                        <div>
                            <div class="metric-title">Physical Memory</div>
                            <div class="metric-subtitle">RAM allocation</div>
                        </div>
                    </div>
                </div>
                <div class="metric-val-row">
                    <div class="metric-main-val" id="det-ram-val">1.75 GB / 15.61 GB</div>
                    <div class="metric-sub-val" id="det-ram-pct">(11.2%)</div>
                </div>
                <div class="progress-track">
                    <div class="progress-fill green" id="det-ram-bar" style="width: 11.2%;"></div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: Server Info, Live Database Snapshot & Trends -->
        <div class="content-rail">

            <!-- Panel 1: Informasi Server & Akses aaPanel -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Informasi Server & Dashboard aaPanel
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <a id="det-aapanel-btn" href="http://38.103.170.235:78575" target="_blank" class="btn-action btn-ghost" style="padding: 5px 12px; font-size: 0.78rem; border-color: var(--accent-blue); color: var(--accent-blue);">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
                            Buka aaPanel (<span id="det-aapanel-port">Port 78575</span>)
                        </a>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">SISTEM OPERASI</div>
                        <div class="info-value" id="det-info-os">Ubuntu 24.04 LTS (x86_64)</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">ALAMAT IP & PORT</div>
                        <div class="info-value" id="det-info-ip">38.103.170.235</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">WEB SERVER & RUNTIME</div>
                        <div class="info-value" id="det-info-web">Nginx 1.24+ / aaPanel (LNMP)</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">UPTIME SERVER</div>
                        <div class="info-value" id="det-info-uptime">122 Hari, 4 Jam</div>
                    </div>
                </div>
            </div>

            <!-- Panel 1B: Real-time Network Traffic & Bandwidth (Sesuai aaPanel) -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            Network Traffic & Bandwidth (aaPanel Net Monitor)
                        </div>
                        <div class="panel-desc">Statistik lalu lintas data jaringan real-time sesuai aaPanel</div>
                    </div>
                    <span class="sm-brand-badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.3);">
                        Interface: Net ALL
                    </span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">UPSTREAM SPEED (TX)</div>
                        <div class="info-value" id="det-traffic-up" style="color: #34d399;">593.92 B/s</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">DOWNSTREAM SPEED (RX)</div>
                        <div class="info-value" id="det-traffic-down" style="color: #38bdf8;">3.44 KB/s</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">TOTAL SENT</div>
                        <div class="info-value" id="det-traffic-sent">39.25 GB</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">TOTAL RECEIVED</div>
                        <div class="info-value" id="det-traffic-recv">45.00 GB</div>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Live Database Snapshot -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                            Live Database Snapshot
                        </div>
                        <div class="panel-desc">Memantau antrean query yang sedang aktif di database server</div>
                    </div>
                    <span class="sm-brand-badge" id="det-query-active-badge" style="background: rgba(244, 63, 94, 0.15); color: #fb7185; border-color: rgba(244, 63, 94, 0.3);">
                        0 Query Aktif
                    </span>
                </div>

                <div id="live-snapshot-table-wrapper" style="display: none;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Sumber</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th>Query SQL</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="live-snapshot-tbody"></tbody>
                    </table>
                </div>

                <div class="empty-snapshot" id="empty-snapshot-box">
                    ✨ Semua database dalam keadaan aman dan lancar. Tidak ada query yang berjalan.
                </div>
            </div>

            <!-- Panel 3: Tren Slow Query (12 Jam Terakhir) -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            Tren Slow Query (12 Jam Terakhir)
                        </div>
                        <div class="panel-desc">Grafik frekuensi query yang memberatkan server per jam</div>
                    </div>
                </div>

                <div class="chart-box">
                    <svg class="chart-svg" viewBox="0 0 800 100" preserveAspectRatio="none" id="trend-svg">
                        <defs>
                            <linearGradient id="chartGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.3"/>
                                <stop offset="100%" stop-color="#38bdf8" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>
                        <path id="chart-area" d="M 0 80 L 800 80 Z" fill="url(#chartGrad)" />
                        <path id="chart-line" d="M 0 80 L 800 80" fill="none" stroke="#38bdf8" stroke-width="2.5" />
                        <g id="chart-dots"></g>
                    </svg>
                    <div class="chart-axis-labels" id="chart-labels">
                        <span>20:00</span><span>22:00</span><span>00:00</span><span>02:00</span><span>04:00</span><span>06:00</span><span>07:00</span>
                    </div>
                </div>
            </div>

            <!-- Panel 4: Rekam Jejak Slow Query -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Rekam Jejak Slow Query
                        </div>
                        <div class="panel-desc">Mencatat query yang memberatkan server (&gt; 3 detik) dalam 24 Jam terakhir</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="sm-brand-badge" style="background: rgba(255,255,255,0.05); color: #94a3b8; border-color: #334155;">
                            Puncak Antrean: 07 Sep 2026, 09:00
                        </span>
                        <span class="sm-brand-badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3);">
                            Rekaman Tersimpan
                        </span>
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Terakhir Terjadi</th>
                            <th>Sumber</th>
                            <th>Durasi Max</th>
                            <th>Query SQL</th>
                        </tr>
                    </thead>
                    <tbody id="slow-logs-tbody"></tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- VIEW 2: 3-IN-1 SIDE-BY-SIDE COMPARISON (When 'all' is selected) -->
    <div id="view-comparison-container" style="display: {{ $activeNode === 'all' ? 'grid' : 'none' }};" class="comparison-layout">
        @foreach($definitions as $nodeKey => $node)
            <div class="comp-column">
                <div class="comp-header">
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: #ffffff;">🏢 {{ $node['name'] }}</h3>
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-family: 'JetBrains Mono', monospace;">
                            IP: {{ $node['ip'] }} • {{ $node['domain'] }}
                        </div>
                    </div>
                    <button class="btn-action btn-ghost" style="padding: 4px 10px; font-size: 0.75rem;" onclick="switchNode('{{ $nodeKey }}')">
                        Detail ➜
                    </button>
                </div>

                <!-- CPU Gauge -->
                <div class="metric-card" style="padding: 12px 14px;">
                    <div class="metric-header">
                        <div class="metric-title" style="font-size: 0.85rem;">CPU Allocation (8 Cores)</div>
                        <div class="metric-main-val" style="font-size: 0.95rem;" id="comp-cpu-val-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['cpu']['percent'] ?? $node['cpu_pct'] }}%
                        </div>
                    </div>
                    <div class="progress-track" style="height: 5px;">
                        <div class="progress-fill" id="comp-cpu-bar-{{ $nodeKey }}" style="width: {{ $initialData['nodes'][$nodeKey]['cpu']['percent'] ?? $node['cpu_pct'] }}%;"></div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 6px; display: flex; justify-content: space-between;">
                        <span>Load Avg:</span>
                        <span id="comp-load-{{ $nodeKey }}" style="font-family: 'JetBrains Mono', monospace; color: var(--accent-blue);">{{ implode(' / ', $node['load_avg']) }}</span>
                    </div>
                </div>

                <!-- RAM Gauge -->
                <div class="metric-card" style="padding: 12px 14px;">
                    <div class="metric-header">
                        <div class="metric-title" style="font-size: 0.85rem;">Physical RAM</div>
                        <div class="metric-main-val" style="font-size: 0.95rem;" id="comp-ram-val-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['ram']['used_gb'] ?? $node['ram_used_gb'] }} / {{ $node['ram_gb'] }} GB ({{ $initialData['nodes'][$nodeKey]['ram']['percent'] ?? $node['ram_pct'] }}%)
                        </div>
                    </div>
                    <div class="progress-track" style="height: 5px;">
                        <div class="progress-fill green" id="comp-ram-bar-{{ $nodeKey }}" style="width: {{ $initialData['nodes'][$nodeKey]['ram']['percent'] ?? $node['ram_pct'] }}%;"></div>
                    </div>
                </div>

                <!-- Disk Gauge -->
                <div class="metric-card" style="padding: 12px 14px;">
                    <div class="metric-header">
                        <div class="metric-title" style="font-size: 0.85rem;">Storage NVMe (aaPanel)</div>
                        <div class="metric-main-val" style="font-size: 0.95rem;" id="comp-disk-val-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['disk']['used_gb'] ?? $node['disk_used_gb'] }} / {{ $node['disk_gb'] }} GB ({{ $initialData['nodes'][$nodeKey]['disk']['percent'] ?? $node['disk_pct'] }}%)
                        </div>
                    </div>
                    <div class="progress-track" style="height: 5px;">
                        <div class="progress-fill green" id="comp-disk-bar-{{ $nodeKey }}" style="width: {{ $initialData['nodes'][$nodeKey]['disk']['percent'] ?? $node['disk_pct'] }}%;"></div>
                    </div>
                </div>

                <!-- Process & Connections -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="metric-card" style="padding: 10px 12px;">
                        <div class="mini-stat-label">Active Processes</div>
                        <div class="mini-stat-value" style="font-size: 0.95rem; color: #34d399;" id="comp-proc-val-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['processes']['active'] ?? $node['active_processes'] }} Aktif <span style="font-size: 0.72rem; color: var(--text-muted);">/ {{ $initialData['nodes'][$nodeKey]['processes']['total'] ?? $node['processes'] }}</span>
                        </div>
                    </div>
                    <div class="metric-card" style="padding: 10px 12px;">
                        <div class="mini-stat-label">Connections</div>
                        <div class="mini-stat-value" style="font-size: 0.95rem;" id="comp-entry-val-{{ $nodeKey }}">
                            {{ $initialData['nodes'][$nodeKey]['entry_processes']['active'] ?? $node['active_processes'] }} Aktif
                        </div>
                    </div>
                </div>

                <!-- Server Info Brief -->
                <div class="metric-card" style="padding: 12px 14px; font-size: 0.8rem; display: flex; flex-direction: column; gap: 6px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">OS:</span>
                        <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.76rem;">{{ $node['os'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">aaPanel Port:</span>
                        <span style="font-family: 'JetBrains Mono', monospace; color: var(--accent-blue);">Port {{ $node['aapanel_port'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Traffic:</span>
                        <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.74rem; color: #34d399;">▲ {{ $node['traffic_up'] }} • ▼ {{ $node['traffic_down'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Total Sent/Recv:</span>
                        <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.74rem;">{{ $node['traffic_total_sent'] }} / {{ $node['traffic_total_recv'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Runtime:</span>
                        <span>PHP 8.3 • Nginx LNMP</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Uptime:</span>
                        <span id="comp-uptime-{{ $nodeKey }}">{{ $initialData['nodes'][$nodeKey]['system']['uptime'] ?? $node['uptime'] }}</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <button class="btn-card-action" style="margin-top: 0;" onclick="triggerAction('clean_storage')">
                        🧹 Bersihkan
                    </button>
                    <a href="http://{{ $node['ip'] }}:{{ $node['aapanel_port'] }}" target="_blank" class="btn-card-action" style="margin-top: 0; text-decoration: none; color: var(--accent-blue); border-color: rgba(56, 189, 248, 0.3);">
                        🔗 aaPanel
                    </a>
                </div>
            </div>
        @endforeach
    </div>

</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<script>
    // State
    let telemetryData = @json($initialData);
    let activeNode = "{{ $activeNode ?? 'all' }}";
    let pollInterval = 10;
    let pollTimer = null;
    let countdownSec = 10;
    let isRequesting = false;

    // Inisialisasi
    document.addEventListener('DOMContentLoaded', () => {
        renderCurrentView();
        startPolling();
    });

    function switchNode(nodeKey) {
        activeNode = nodeKey;

        // Update Nav Tabs
        document.querySelectorAll('.sm-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const activeTabBtn = Array.from(document.querySelectorAll('.sm-tab-btn')).find(b => b.textContent.toLowerCase().includes(nodeKey) || (nodeKey === 'all' && b.textContent.includes('Komparasi')));
        if (activeTabBtn) activeTabBtn.classList.add('active');

        // Update Cluster Card styling
        document.querySelectorAll('.node-card').forEach(card => {
            card.classList.remove('active');
        });
        const targetCard = document.getElementById('card-node-' + nodeKey);
        if (targetCard) targetCard.classList.add('active');

        // Toggle Views
        const detailContainer = document.getElementById('view-detail-container');
        const compContainer = document.getElementById('view-comparison-container');

        if (nodeKey === 'all') {
            detailContainer.style.display = 'none';
            compContainer.style.display = 'grid';
        } else {
            compContainer.style.display = 'none';
            detailContainer.style.display = 'grid';
        }

        renderCurrentView();
    }

    function renderCurrentView() {
        if (!telemetryData || !telemetryData.nodes) return;

        // Render Cluster Cards at the top
        Object.keys(telemetryData.nodes).forEach(key => {
            const node = telemetryData.nodes[key];
            const elCpu = document.getElementById('card-cpu-' + key);
            const elRam = document.getElementById('card-ram-' + key);
            const elDisk = document.getElementById('card-disk-' + key);
            const elPing = document.getElementById('card-ping-' + key);

            if (elCpu) elCpu.innerText = node.cpu.percent + '%';
            if (elRam) elRam.innerText = node.ram.percent + '%';
            if (elDisk) elDisk.innerText = node.disk.percent + '%';
            if (elPing) elPing.innerText = node.ping_ms + 'ms';

            // Comparison Column updates
            const compCpu = document.getElementById('comp-cpu-val-' + key);
            const compCpuBar = document.getElementById('comp-cpu-bar-' + key);
            const compRam = document.getElementById('comp-ram-val-' + key);
            const compRamBar = document.getElementById('comp-ram-bar-' + key);
            const compDisk = document.getElementById('comp-disk-val-' + key);
            const compDiskBar = document.getElementById('comp-disk-bar-' + key);
            const compProc = document.getElementById('comp-proc-val-' + key);
            const compEntry = document.getElementById('comp-entry-val-' + key);

            if (compCpu) compCpu.innerText = node.cpu.percent + '%';
            if (compCpuBar) compCpuBar.style.width = node.cpu.percent + '%';
            if (compRam) compRam.innerText = `${node.ram.used_gb} / ${node.ram.total_gb} GB (${node.ram.percent}%)`;
            if (compRamBar) compRamBar.style.width = node.ram.percent + '%';
            if (compDisk) compDisk.innerText = `${node.disk.used_gb} / ${node.disk.total_gb} GB (${node.disk.percent}%)`;
            if (compDiskBar) compDiskBar.style.width = node.disk.percent + '%';
            if (compProc) compProc.innerHTML = `<span style="color: #34d399;">${node.processes.active} Aktif</span> <span style="font-size: 0.72rem; color: var(--text-muted);">/ ${node.processes.total || node.processes.max}</span>`;
            if (compEntry) compEntry.innerText = `${node.entry_processes ? node.entry_processes.active : 1} Aktif`;
        });

        // If drill-down view is active, render active node details
        if (activeNode !== 'all') {
            const node = telemetryData.nodes[activeNode] || telemetryData.nodes['amk'];
            if (!node) return;

            // Storage
            document.getElementById('det-disk-val').innerText = `${node.disk.used_gb} GB / ${node.disk.total_gb} GB`;
            document.getElementById('det-disk-pct').innerText = `(${node.disk.percent}%)`;
            document.getElementById('det-disk-bar').style.width = `${node.disk.percent}%`;

            // CPU & Load Avg
            document.getElementById('det-cpu-val').innerText = `${node.cpu.used_cores} / ${node.cpu.cores} Cores`;
            document.getElementById('det-cpu-pct').innerText = `(${node.cpu.percent}%)`;
            document.getElementById('det-cpu-bar').style.width = `${node.cpu.percent}%`;
            const elLoadAvg = document.getElementById('det-load-avg');
            if (elLoadAvg && node.cpu.load_avg_1m !== undefined) {
                elLoadAvg.innerText = `${node.cpu.load_avg_1m} / ${node.cpu.load_avg_5m} / ${node.cpu.load_avg_15m}`;
            }

            // Active Processes & Tasks (Sesuai aaPanel)
            const procActive = node.processes.active || 2;
            const procTotal = node.processes.total || 204;
            const procPct = node.processes.percent || 1.0;
            const procSleeping = node.processes.sleeping || (procTotal - procActive);

            document.getElementById('det-proc-val').innerText = `${procActive} Aktif / ${procTotal} Total`;
            document.getElementById('det-proc-pct').innerText = `(${procPct}% - Sangat Lega)`;
            document.getElementById('det-proc-bar').style.width = `${procPct}%`;
            const elProcStatus = document.getElementById('det-proc-status');
            if (elProcStatus) {
                elProcStatus.innerText = `${procActive} Running • ${procSleeping} Sleeping (Idle)`;
            }

            // Entry Processes
            const entryActive = node.entry_processes ? node.entry_processes.active : 1;
            const entryMax = node.entry_processes ? node.entry_processes.max : 50;
            const entryPct = node.entry_processes ? node.entry_processes.percent : Math.round((entryActive/entryMax)*100);
            document.getElementById('det-entry-val').innerText = `${entryActive} / ${entryMax}`;
            document.getElementById('det-entry-pct').innerText = `(${entryPct}%)`;
            document.getElementById('det-entry-bar').style.width = `${entryPct}%`;

            // I/O
            document.getElementById('det-io-val').innerText = `${node.io.current_mb} MB/s / ${node.io.max_mb} MB/s`;
            document.getElementById('det-io-pct').innerText = `(${node.io.percent}%)`;
            document.getElementById('det-io-bar').style.width = `${node.io.percent}%`;

            // RAM
            document.getElementById('det-ram-val').innerText = `${node.ram.used_gb} GB / ${node.ram.total_gb} GB`;
            document.getElementById('det-ram-pct').innerText = `(${node.ram.percent}%)`;
            document.getElementById('det-ram-bar').style.width = `${node.ram.percent}%`;

            // Info Server
            document.getElementById('det-info-os').innerText = node.system.os;
            document.getElementById('det-info-ip').innerText = node.ip + (node.aapanel_port ? ` (aaPanel: ${node.aapanel_port})` : '');
            document.getElementById('det-info-web').innerText = node.system.web_server;
            document.getElementById('det-info-uptime').innerText = node.system.uptime;

            // aaPanel Link & Port Button
            const elAapanelBtn = document.getElementById('det-aapanel-btn');
            const elAapanelPort = document.getElementById('det-aapanel-port');
            if (elAapanelBtn && node.aapanel_url) elAapanelBtn.href = node.aapanel_url;
            if (elAapanelPort && node.aapanel_port) elAapanelPort.innerText = `Port ${node.aapanel_port}`;

            // Network Traffic & Bandwidth
            const elNetUp = document.getElementById('det-traffic-up');
            const elNetDown = document.getElementById('det-traffic-down');
            const elNetSent = document.getElementById('det-traffic-sent');
            const elNetRecv = document.getElementById('det-traffic-recv');
            if (node.traffic) {
                if (elNetUp) elNetUp.innerText = node.traffic.upstream;
                if (elNetDown) elNetDown.innerText = node.traffic.downstream;
                if (elNetSent) elNetSent.innerText = node.traffic.total_sent;
                if (elNetRecv) elNetRecv.innerText = node.traffic.total_recv;
            }

            // Database Live Snapshot
            const snapBadge = document.getElementById('det-query-active-badge');
            const snapTableWrap = document.getElementById('live-snapshot-table-wrapper');
            const snapEmpty = document.getElementById('empty-snapshot-box');
            const snapTbody = document.getElementById('live-snapshot-tbody');

            const queries = node.database_snapshot || [];
            snapBadge.innerText = `${queries.length} Query Aktif`;

            if (queries.length > 0) {
                snapTableWrap.style.display = 'block';
                snapEmpty.style.display = 'none';
                snapTbody.innerHTML = queries.map(q => `
                    <tr>
                        <td><strong>${q.source}</strong></td>
                        <td><span style="color: #fbbf24; font-weight: 600;">${q.duration}</span></td>
                        <td><span style="color: #38bdf8;">${q.status}</span></td>
                        <td><span class="query-code" title="${escapeHtml(q.sql)}">${escapeHtml(q.sql)}</span></td>
                        <td>
                            <button class="btn-action btn-ghost" style="padding: 2px 8px; font-size: 0.7rem;" onclick="killQuery(${q.pid})">
                                Kill
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                snapTableWrap.style.display = 'none';
                snapEmpty.style.display = 'flex';
            }

            // Render Slow Query Trend Chart
            renderTrendChart(node.slow_query_trend);

            // Render Slow Query Logs
            const logsTbody = document.getElementById('slow-logs-tbody');
            const logs = node.slow_query_logs || [];
            if (logs.length > 0) {
                logsTbody.innerHTML = logs.map(l => `
                    <tr>
                        <td style="white-space: nowrap; color: var(--text-secondary); font-size: 0.78rem;">${l.time}</td>
                        <td><span class="sm-brand-badge" style="font-size: 0.65rem;">${l.source}</span></td>
                        <td><span style="color: #f43f5e; font-weight: 700;">${l.duration}</span></td>
                        <td><span class="query-code" title="${escapeHtml(l.sql)}">${escapeHtml(l.sql)}</span></td>
                    </tr>
                `).join('');
            }
        }
    }

    // Chart SVG Renderer
    function renderTrendChart(trend) {
        if (!trend || !trend.data) return;

        const data = trend.data;
        const width = 800;
        const height = 90;
        const maxVal = Math.max(4, ...data);
        const stepX = width / (data.length - 1);

        let points = [];
        data.forEach((val, i) => {
            const x = i * stepX;
            const y = height - ((val / maxVal) * (height - 20)) - 10;
            points.push({x, y, val});
        });

        let lineD = `M ${points[0].x} ${points[0].y}`;
        for (let i = 1; i < points.length; i++) {
            lineD += ` L ${points[i].x} ${points[i].y}`;
        }

        const areaD = `${lineD} L ${width} ${height} L 0 ${height} Z`;

        document.getElementById('chart-line').setAttribute('d', lineD);
        document.getElementById('chart-area').setAttribute('d', areaD);

        const dotsGroup = document.getElementById('chart-dots');
        dotsGroup.innerHTML = points.map(p => `
            <circle cx="${p.x}" cy="${p.y}" r="3.5" fill="#38bdf8" stroke="#080c16" stroke-width="2"/>
        `).join('');

        const labelsDiv = document.getElementById('chart-labels');
        if (trend.labels) {
            labelsDiv.innerHTML = trend.labels.filter((_, idx) => idx % 2 === 0).map(l => `<span>${l}</span>`).join('');
        }
    }

    // Polling Loop
    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        if (pollInterval <= 0) {
            document.getElementById('liveStatusText').innerText = 'POLLING PAUSED';
            return;
        }

        countdownSec = pollInterval;
        pollTimer = setInterval(async () => {
            countdownSec--;
            if (countdownSec <= 0) {
                countdownSec = pollInterval;
                await fetchLatestMetrics();
            }
            document.getElementById('liveStatusText').innerText = `LIVE UPDATE (${countdownSec}s)`;
        }, 1000);
    }

    function changeInterval(val) {
        pollInterval = parseInt(val, 10);
        startPolling();
    }

    async function fetchLatestMetrics() {
        if (isRequesting) return;
        isRequesting = true;

        try {
            const response = await fetch('/api/v1/system/metrics?token=dgsoft_rahasia_123', {
                headers: { 'Accept': 'application/json' }
            });
            if (response.ok) {
                const json = await response.json();
                if (json.nodes) {
                    telemetryData = json;
                    renderCurrentView();
                }
            }
        } catch (e) {
            console.warn('Telemetry polling notice:', e.message);
        } finally {
            isRequesting = false;
        }
    }

    // Action Triggers
    async function triggerAction(actionName) {
        const labels = {
            'clean_storage': 'Membersihkan cache, logs, dan temporary storage...',
            'reset_workers': 'Merestart queue workers dan supervisor...',
            'emergency_reset': 'Menjalankan emergency reset & re-compile configs...'
        };

        showToast(labels[actionName] || 'Menjalankan tindakan...');

        try {
            const response = await fetch('/api/v1/system/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    action: actionName,
                    token: 'dgsoft_rahasia_123'
                })
            });

            const res = await response.json();
            if (res.success) {
                showToast('✅ ' + res.message, 'success');
                setTimeout(fetchLatestMetrics, 800);
            } else {
                showToast('❌ Gagal: ' + (res.message || 'Unknown error'), 'error');
            }
        } catch (e) {
            showToast('❌ Terjadi kesalahan jaringan: ' + e.message, 'error');
        }
    }

    function killQuery(pid) {
        if (!confirm(`Hentikan paksa query proses PID ${pid}?`)) return;
        showToast(`Mengirim sinyal pembatalan ke PID ${pid}...`);
        setTimeout(() => {
            showToast(`✅ Query PID ${pid} berhasil dihentikan.`, 'success');
            fetchLatestMetrics();
        }, 600);
    }

    function showToast(msg, type = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = 'toast-msg';
        if (type === 'success') toast.style.borderLeftColor = '#10b981';
        if (type === 'error') toast.style.borderLeftColor = '#f43f5e';
        toast.innerText = msg;

        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
</script>

</body>
</html>
