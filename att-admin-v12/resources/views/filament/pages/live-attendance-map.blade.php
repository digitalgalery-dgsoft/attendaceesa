<x-filament-panels::page>
@php
    $mapData = $this->getMapData();
    $employees = $mapData['employees'] ?? [];
    $summary = $mapData['summary'] ?? [];
    $unmapped = $mapData['unmapped'] ?? [];
    $allPrincipals = $this->getActivePrincipals();
    $allBranches = $this->getActiveBranches();
@endphp

    {{-- Leaflet Core CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <style>
        .live-map-wrapper {
            display: flex;
            flex-direction: column;
            gap: 18px;
            font-family: inherit;
        }

        /* ─── Top Control & Status Bar ─── */
        .live-status-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: 16px;
            padding: 18px 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .live-badge-pulse {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseRadar 1.8s infinite;
        }

        @keyframes pulseRadar {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* ─── KPI Cards ─── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 14px;
        }
        @media (min-width: 640px) {
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        .kpi-stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }
        .dark .kpi-stat-card {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }
        .kpi-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.08);
        }

        .kpi-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ─── Filter Bar ─── */
        .filter-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 50;
        }
        .dark .filter-panel {
            background: #1e293b;
            border-color: #334155;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 14px;
        }
        @media (min-width: 640px) {
            .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .filter-grid { grid-template-columns: 2.2fr 2.2fr 1.6fr 2.5fr auto; align-items: flex-end; }
        }

        /* ─── Searchable Dropdown Component ─── */
        .searchable-select-container {
            position: relative;
            width: 100%;
        }

        .searchable-select-trigger {
            width: 100%;
            height: 40px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            cursor: pointer;
            outline: none;
            transition: all 0.15s ease;
            box-sizing: border-box;
            text-align: left;
        }
        .dark .searchable-select-trigger {
            background: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        .searchable-select-trigger:hover {
            border-color: #94a3b8;
        }
        .searchable-select-trigger.is-active {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .searchable-select-dropdown {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            width: 100%;
            min-width: 220px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.18), 0 4px 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .dark .searchable-select-dropdown {
            background: #0f172a;
            border-color: #334155;
            box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.5);
        }

        .searchable-select-searchbox {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
        }
        .dark .searchable-select-searchbox {
            background: #1e293b;
            border-color: #334155;
        }

        .searchable-select-input {
            width: 100%;
            padding: 6px 10px;
            font-size: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: #0f172a;
            outline: none;
        }
        .dark .searchable-select-input {
            background: #0f172a;
            border-color: #334155;
            color: #ffffff;
        }

        .searchable-select-options {
            max-height: 220px;
            overflow-y: auto;
            padding: 4px;
        }

        .searchable-select-option {
            padding: 8px 12px;
            font-size: 12px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #334155;
            transition: background 0.12s;
        }
        .dark .searchable-select-option {
            color: #e2e8f0;
        }
        .searchable-select-option:hover {
            background: #f1f5f9;
        }
        .dark .searchable-select-option:hover {
            background: #1e293b;
        }
        .searchable-select-option.is-selected {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
        }
        .dark .searchable-select-option.is-selected {
            background: #1e3a8a44;
            color: #60a5fa;
        }

        .custom-select, .custom-input {
            width: 100%;
            height: 40px;
            padding: 8px 12px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #0f172a;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .dark .custom-select, .dark .custom-input {
            background: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        .custom-select:focus, .custom-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* ─── Map & Sidebar Split Layout ─── */
        .map-layout-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        @media (min-width: 1024px) {
            .map-layout-grid {
                grid-template-columns: 1fr 360px;
            }
        }

        .map-viewport-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .dark .map-viewport-card {
            background: #1e293b;
            border-color: #334155;
        }

        #live-map-container {
            width: 100%;
            height: 640px;
            z-index: 10;
        }

        /* Fullscreen map state */
        .map-fullscreen-active #live-map-container {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 999999 !important;
            border-radius: 0 !important;
        }

        /* ─── Map Float Controls ─── */
        .map-floating-toolbar {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 999;
            display: flex;
            gap: 8px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            padding: 6px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .dark .map-floating-toolbar {
            background: rgba(15, 23, 42, 0.95);
            border-color: rgba(51, 65, 85, 0.8);
        }

        .toolbar-btn {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            background: #f1f5f9;
            color: #334155;
        }
        .dark .toolbar-btn {
            background: #1e293b;
            color: #e2e8f0;
        }
        .toolbar-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .dark .toolbar-btn:hover {
            background: #334155;
            color: #ffffff;
        }
        .toolbar-btn.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        }

        /* ─── Sidebar List ─── */
        .employee-sidebar-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            height: 640px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .dark .employee-sidebar-card {
            background: #1e293b;
            border-color: #334155;
        }

        .sidebar-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .dark .sidebar-header {
            background: #0f172a;
            border-color: #334155;
        }

        .sidebar-list-body {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .emp-list-item {
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .dark .emp-list-item {
            background: #1e293b;
            border-color: #334155;
        }
        .emp-list-item:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateX(3px);
        }
        .dark .emp-list-item:hover {
            background: #1e3a8a33;
            border-color: #60a5fa;
        }

        .emp-list-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            flex-shrink: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 13px;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            position: relative;
        }
        .emp-list-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ─── Leaflet Custom Pin Marker ─── */
        .leaflet-div-icon,
        .leaflet-emp-custom-icon {
            background: transparent !important;
            border: none !important;
        }

        .emp-map-pin {
            width: 46px;
            height: 56px;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            position: relative;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .emp-map-pin:hover {
            transform: scale(1.22) translateY(-4px);
            z-index: 99999 !important;
        }

        .emp-pin-disc {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 3px solid #10b981;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .emp-marker-avatar {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }
        .emp-marker-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .emp-initials {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: 0.5px;
            user-select: none;
        }

        .emp-pin-arrow {
            width: 0;
            height: 0;
            border-left: 7px solid transparent;
            border-right: 7px solid transparent;
            border-top: 8px solid #10b981;
            margin: 0 auto;
            filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.3));
        }

        .emp-pin-shadow {
            width: 18px;
            height: 5px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 50%;
            margin: -2px auto 0;
            filter: blur(2px);
        }

        /* Marker Pulse Badge */
        .emp-live-pulse {
            position: absolute;
            bottom: 0px;
            right: 0px;
            width: 12px;
            height: 12px;
            background: #10b981;
            border: 2px solid #ffffff;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseRadar 1.8s infinite;
        }

        .emp-checkin-badge {
            position: absolute;
            bottom: 0px;
            right: 0px;
            width: 10px;
            height: 10px;
            background: #3b82f6;
            border: 2px solid #ffffff;
            border-radius: 50%;
        }

        /* ─── Marker & Sidebar Selected Highlight ─── */
        .emp-map-pin.is-active-selected {
            transform: scale(1.28) translateY(-8px) !important;
            z-index: 999999 !important;
        }
        .emp-map-pin.is-active-selected .emp-pin-disc {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 4px #ffffff, 0 0 0 8px #f59e0b, 0 16px 32px rgba(245, 158, 11, 0.6) !important;
            animation: activePinPulse 1.6s infinite alternate ease-in-out;
        }
        @keyframes activePinPulse {
            0% { box-shadow: 0 0 0 3px #ffffff, 0 0 0 6px #f59e0b, 0 10px 24px rgba(245, 158, 11, 0.5); }
            100% { box-shadow: 0 0 0 5px #ffffff, 0 0 0 12px #f59e0b, 0 18px 36px rgba(245, 158, 11, 0.8); }
        }

        .emp-list-item.is-selected {
            border-color: #2563eb !important;
            background: #eff6ff !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
            transform: translateX(4px);
        }
        .dark .emp-list-item.is-selected {
            background: #1e3a8a4d !important;
            border-color: #60a5fa !important;
        }

        /* ─── Floating Persistent Employee Detail Panel (Cara Lain 100% Persisten) ─── */
        .emp-floating-detail-panel {
            position: absolute;
            top: 16px;
            left: 56px;
            width: 370px;
            max-width: calc(100% - 72px);
            max-height: calc(100% - 32px);
            background: #ffffff;
            border-radius: 18px;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            animation: slideInDetailCard 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            scrollbar-width: thin;
        }
        .dark .emp-floating-detail-panel {
            background: #0f172a;
            border-color: #334155;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.1);
            color: #f8fafc;
        }
        @keyframes slideInDetailCard {
            from {
                opacity: 0;
                transform: translateY(-14px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .map-fullscreen-active .emp-floating-detail-panel {
            position: fixed !important;
            top: 20px !important;
            left: 20px !important;
            z-index: 1000001 !important;
        }
        .map-fullscreen-active .map-floating-toolbar {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 1000002 !important;
        }

        @media (max-width: 640px) {
            .emp-floating-detail-panel {
                top: auto;
                bottom: 12px;
                left: 12px;
                right: 12px;
                width: auto;
                max-width: none;
                max-height: 80vh;
            }
        }

        .panel-header-live {
            background: linear-gradient(135deg, #065f46 0%, #047857 100%);
            color: #ffffff;
            padding: 16px 18px;
            position: relative;
        }
        .panel-header-checkin {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
            padding: 16px 18px;
            position: relative;
        }

        .panel-close-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: 800;
            transition: all 0.15s ease;
        }
        .panel-close-btn:hover {
            background: rgba(239, 68, 68, 0.9);
            transform: scale(1.1);
        }

        .panel-body-content {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #ffffff;
        }
        .dark .panel-body-content {
            background: #0f172a;
        }

        .panel-action-bar {
            padding: 12px 18px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dark .panel-action-bar {
            background: #1e293b;
            border-color: #334155;
        }
    </style>

    <div class="live-map-wrapper" id="live-map-wrapper" x-data x-init="$nextTick(() => { setTimeout(() => { window.initOrUpdateMap && window.initOrUpdateMap(); }, 80); })">
        
        {{-- 1. Status Bar Header --}}
        <div class="live-status-bar">
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);">
                    📍
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h2 style="font-size: 18px; font-weight: 800; letter-spacing: -0.01em; margin: 0;">Peta Presensi Karyawan Real-Time</h2>
                        <span class="live-badge-pulse">
                            <span class="pulse-dot"></span> LIVE MONITORING
                        </span>
                    </div>
                    <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0 0;">
                        Memantau titik koordinat karyawan yang sedang check-in di seluruh wilayah & prinsiple.
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div style="text-align: right; font-size: 12px; color: #94a3b8;">
                    Waktu Server: <strong style="color: #ffffff; font-family: monospace;" id="live-clock-display">{{ $summary['timestamp'] ?? date('H:i:s') . ' WIB' }}</strong>
                </div>

                {{-- Auto Refresh Toggle --}}
                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; background: rgba(255,255,255,0.1); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;">
                    <input type="checkbox" id="auto-refresh-toggle" style="accent-color: #10b981; cursor: pointer;">
                    <span>Auto-Refresh (30s)</span>
                    <span id="refresh-countdown" style="display:none; font-family: monospace; color: #34d399; font-weight: 700;"></span>
                </label>
            </div>
        </div>

        {{-- 2. KPI Stat Cards --}}
        <div class="kpi-grid">
            {{-- Total Sedang Check-in --}}
            <div class="kpi-stat-card">
                <div>
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Sedang Check-in</span>
                    <div style="font-size: 26px; font-weight: 900; color: #0f172a; margin-top: 4px;" id="kpi-total-checkedin" class="dark:text-white">
                        {{ $summary['total_checked_in'] ?? count($employees) }}
                    </div>
                    <span style="font-size: 11px; color: #10b981; font-weight: 600;">● Sesi Presensi Aktif</span>
                </div>
                <div class="kpi-icon" style="background: #ecfdf5; color: #059669;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>

            {{-- GPS Live Tracking --}}
            <div class="kpi-stat-card">
                <div>
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">GPS Live Tracking</span>
                    <div style="font-size: 26px; font-weight: 900; color: #059669; margin-top: 4px;" id="kpi-total-live">
                        {{ $summary['total_live_gps'] ?? 0 }}
                    </div>
                    <span style="font-size: 11px; color: #059669; font-weight: 600;">Koordinat Bergerak Terkini</span>
                </div>
                <div class="kpi-icon" style="background: #ecfdf5; color: #10b981;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>

            {{-- Titik Check-in Saja --}}
            <div class="kpi-stat-card">
                <div>
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Titik Check-in</span>
                    <div style="font-size: 26px; font-weight: 900; color: #2563eb; margin-top: 4px;" id="kpi-total-checkin">
                        {{ $summary['total_checkin_point'] ?? 0 }}
                    </div>
                    <span style="font-size: 11px; color: #2563eb; font-weight: 600;">Lokasi Saat Presensi Masuk</span>
                </div>
                <div class="kpi-icon" style="background: #eff6ff; color: #2563eb;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>

            {{-- Prinsiple & Area Tercover --}}
            <div class="kpi-stat-card">
                <div>
                    <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Prinsiple & Area</span>
                    <div style="font-size: 26px; font-weight: 900; color: #7c3aed; margin-top: 4px;" id="kpi-coverage">
                        {{ $summary['active_principals_count'] ?? 0 }} <span style="font-size: 14px; font-weight: 600; color: #64748b;">Prinsiple</span>
                    </div>
                    <span style="font-size: 11px; color: #7c3aed; font-weight: 600;">{{ $summary['active_branches_count'] ?? 0 }} Area / Cabang Aktif</span>
                </div>
                <div class="kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 3. Filter Bar (Searchable Prinsiple, Searchable Area, Tipe Titik, Pencarian) --}}
        <div class="filter-panel">
            <div class="filter-grid">
                
                {{-- 1. Searchable Filter Prinsiple (Hanya yang sedang memiliki karyawan check-in) --}}
                <div 
                    wire:key="filter-principal-container-{{ count($allPrincipals) }}"
                    x-data="{
                        open: false,
                        search: '',
                        selectedId: @entangle('selectedPrincipalId').live,
                        options: @js($allPrincipals),
                        get selectedLabel() {
                            if (!this.selectedId) return '-- Semua Prinsiple ({{ count($allPrincipals) }} Aktif) --';
                            const found = this.options.find(o => String(o.id) === String(this.selectedId));
                            return found ? found.name : '-- Semua Prinsiple --';
                        },
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            const q = this.search.toLowerCase();
                            return this.options.filter(o => o.name.toLowerCase().includes(q));
                        },
                        select(id) {
                            this.selectedId = id;
                            this.open = false;
                            this.search = '';
                        },
                        clear() {
                            this.selectedId = '';
                            this.open = false;
                            this.search = '';
                        }
                    }"
                    class="searchable-select-container"
                    @click.outside="open = false"
                >
                    <label style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #334155;" class="dark:text-slate-300">
                        <span>🏢 Filter Prinsiple</span>
                        <span style="font-size: 11px; font-weight: 600; color: #0284c7; background: #e0f2fe; padding: 1px 8px; border-radius: 999px;">{{ count($allPrincipals) }} Aktif</span>
                    </label>

                    <button 
                        type="button" 
                        @click="open = !open; if(open) $nextTick(() => $refs.principalSearchInput.focus())"
                        class="searchable-select-trigger"
                        :class="{'is-active': open || selectedId}"
                    >
                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="selectedLabel"></span>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span 
                                x-show="selectedId" 
                                @click.stop="clear()" 
                                style="color: #94a3b8; font-size: 14px; font-weight: bold; padding: 0 4px;"
                                title="Hapus filter"
                            >✕</span>
                            <span style="font-size: 10px; color: #64748b; transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg);' : ''">▼</span>
                        </div>
                    </button>

                    <div x-show="open" style="display: none;" class="searchable-select-dropdown">
                        <div class="searchable-select-searchbox">
                            <input 
                                x-ref="principalSearchInput"
                                x-model="search"
                                type="text" 
                                class="searchable-select-input"
                                placeholder="Cari nama prinsiple aktif..." 
                                @keydown.escape="open = false"
                            />
                        </div>

                        <div class="searchable-select-options">
                            <div 
                                @click="clear()"
                                class="searchable-select-option"
                                :class="{'is-selected': !selectedId}"
                            >
                                <span>-- Semua Prinsiple ({{ count($allPrincipals) }} Aktif) --</span>
                                <span x-show="!selectedId">✓</span>
                            </div>

                            <template x-for="opt in filteredOptions" :key="opt.id">
                                <div 
                                    @click="select(String(opt.id))"
                                    class="searchable-select-option"
                                    :class="{'is-selected': String(selectedId) === String(opt.id)}"
                                >
                                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 8px;">
                                        <span x-text="opt.name" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                            <span style="background: #e0f2fe; color: #0284c7; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px;" x-text="opt.count + ' check-in'"></span>
                                            <span x-show="String(selectedId) === String(opt.id)">✓</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="filteredOptions.length === 0" style="padding: 12px; text-align: center; font-size: 11px; color: #94a3b8;">
                                Tidak ada prinsiple yang cocok
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Searchable Filter Area / Cabang (Hanya yang sedang memiliki karyawan check-in) --}}
                <div 
                    wire:key="filter-branch-container-{{ $selectedPrincipalId ?? 'all' }}-{{ count($allBranches) }}"
                    x-data="{
                        open: false,
                        search: '',
                        selectedId: @entangle('selectedBranchId').live,
                        options: @js($allBranches),
                        get selectedLabel() {
                            if (!this.selectedId) return '-- Semua Area / Cabang ({{ count($allBranches) }} Aktif) --';
                            const found = this.options.find(o => String(o.id) === String(this.selectedId));
                            return found ? found.name : '-- Semua Area / Cabang --';
                        },
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            const q = this.search.toLowerCase();
                            return this.options.filter(o => o.name.toLowerCase().includes(q));
                        },
                        select(id) {
                            this.selectedId = id;
                            this.open = false;
                            this.search = '';
                        },
                        clear() {
                            this.selectedId = '';
                            this.open = false;
                            this.search = '';
                        }
                    }"
                    class="searchable-select-container"
                    @click.outside="open = false"
                >
                    <label style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #334155;" class="dark:text-slate-300">
                        <span>📍 Filter Area / Cabang</span>
                        <span style="font-size: 11px; font-weight: 600; color: #7c3aed; background: #f5f3ff; padding: 1px 8px; border-radius: 999px;">{{ count($allBranches) }} Aktif</span>
                    </label>

                    <button 
                        type="button" 
                        @click="open = !open; if(open) $nextTick(() => $refs.branchSearchInput.focus())"
                        class="searchable-select-trigger"
                        :class="{'is-active': open || selectedId}"
                    >
                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="selectedLabel"></span>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span 
                                x-show="selectedId" 
                                @click.stop="clear()" 
                                style="color: #94a3b8; font-size: 14px; font-weight: bold; padding: 0 4px;"
                                title="Hapus filter"
                            >✕</span>
                            <span style="font-size: 10px; color: #64748b; transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg);' : ''">▼</span>
                        </div>
                    </button>

                    <div x-show="open" style="display: none;" class="searchable-select-dropdown">
                        <div class="searchable-select-searchbox">
                            <input 
                                x-ref="branchSearchInput"
                                x-model="search"
                                type="text" 
                                class="searchable-select-input"
                                placeholder="Cari nama area / cabang aktif..." 
                                @keydown.escape="open = false"
                            />
                        </div>

                        <div class="searchable-select-options">
                            <div 
                                @click="clear()"
                                class="searchable-select-option"
                                :class="{'is-selected': !selectedId}"
                            >
                                <span>-- Semua Area / Cabang ({{ count($allBranches) }} Aktif) --</span>
                                <span x-show="!selectedId">✓</span>
                            </div>

                            <template x-for="opt in filteredOptions" :key="opt.id">
                                <div 
                                    @click="select(String(opt.id))"
                                    class="searchable-select-option"
                                    :class="{'is-selected': String(selectedId) === String(opt.id)}"
                                >
                                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 8px;">
                                        <span x-text="opt.name" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                            <span style="background: #f1f5f9; color: #475569; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px;" x-text="opt.count + ' check-in'"></span>
                                            <span x-show="String(selectedId) === String(opt.id)">✓</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="filteredOptions.length === 0" style="padding: 12px; text-align: center; font-size: 11px; color: #94a3b8;">
                                Tidak ada area yang cocok
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Filter Tipe Titik --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #334155;" class="dark:text-slate-300">
                        🛰️ Sumber Titik
                    </label>
                    <select wire:model.live="sourceFilter" class="custom-select">
                        <option value="all">Semua Titik (Live & Presensi)</option>
                        <option value="live">Hanya Live Tracking (Bergerak)</option>
                        <option value="checkin">Hanya Titik Presensi Check-in</option>
                    </select>
                </div>

                {{-- 4. Pencarian Cepat Nama / NIK --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #334155;" class="dark:text-slate-300">
                        🔍 Cari Karyawan / NIK / Jabatan
                    </label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchQuery" 
                        class="custom-input" 
                        placeholder="Ketik nama, NIK, atau jabatan..."
                    />
                </div>

                {{-- 5. Reset Filter Button --}}
                <div>
                    <button 
                        type="button" 
                        wire:click="resetFilters" 
                        style="width: 100%; height: 40px; padding: 0 16px; font-size: 12px; font-weight: 700; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.15s ease;"
                        onmouseover="this.style.background='#e2e8f0';"
                        onmouseout="this.style.background='#f8fafc';"
                    >
                        🔄 Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- 4. Main Map & Sidebar Section --}}
        <div class="map-layout-grid">
            
            {{-- Map Viewport --}}
            <div class="map-viewport-card" id="map-viewport-card">
                
                {{-- Floating Toolbar on Map --}}
                <div class="map-floating-toolbar">
                    <button type="button" class="toolbar-btn active" id="btn-layer-street" title="Peta Jalan Raya Standard">
                        🛣️ Jalan
                    </button>
                    <button type="button" class="toolbar-btn" id="btn-layer-satellite" title="Tampilan Citra Satelit Realistis">
                        🛰️ Satelit
                    </button>
                    <div style="width: 1px; height: 18px; background: #cbd5e1; margin: 0 2px;"></div>
                    <button type="button" class="toolbar-btn" id="btn-recenter-all" title="Pusatkan Peta ke Seluruh Karyawan Aktif">
                        🎯 Fokus Karyawan
                    </button>
                    <button type="button" class="toolbar-btn" id="btn-fullscreen-toggle" title="Layar Penuh">
                        ⛶ Fullscreen
                    </button>
                </div>

                {{-- Leaflet Map Canvas (wire:ignore to prevent Livewire DOM morph reloads) --}}
                <div id="live-map-container" wire:ignore></div>

                {{-- Floating Persistent Employee Detail Panel (Cara Lain yang 100% Persisten & Murni Ditutup Manual) --}}
                <div id="emp-floating-detail-panel" class="emp-floating-detail-panel" style="display: none;" wire:ignore></div>
            </div>

            {{-- Sidebar List Karyawan --}}
            <div class="employee-sidebar-card">
                <div class="sidebar-header">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 13px; font-weight: 800; color: #0f172a;" class="dark:text-white">
                            Daftar Karyawan Terpantau
                        </span>
                        <span style="font-size: 11px; font-weight: 700; background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 999px;" id="sidebar-count-badge">
                            {{ count($employees) }} Titik
                        </span>
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                        Klik pada nama untuk mengarahkan peta ke titik lokasi karyawan.
                    </div>
                </div>

                <div class="sidebar-list-body" id="employee-list-container">
                    @forelse ($employees as $emp)
                        <div 
                            class="emp-list-item" 
                            onclick="window.focusEmployeeOnMap({{ $emp['id'] }})"
                            data-emp-id="{{ $emp['id'] }}"
                            title="Klik untuk melihat di peta"
                        >
                            <div class="emp-list-avatar" style="border-color: {{ $emp['badge_color'] }}; background: {{ $emp['avatar_color'] }};">
                                @if(!empty($emp['photo_url']))
                                    <img 
                                        src="{{ $emp['photo_url'] }}" 
                                        alt="{{ $emp['name'] }}" 
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    />
                                    <span style="display:none; width:100%; height:100%; align-items:center; justify-content:center;">
                                        {{ $emp['initials'] }}
                                    </span>
                                @else
                                    <span>{{ $emp['initials'] }}</span>
                                @endif
                                
                                @if($emp['is_live'])
                                    <span class="emp-live-pulse" style="width: 10px; height: 10px;"></span>
                                @endif
                            </div>

                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 4px;">
                                    <span style="font-size: 13px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" class="dark:text-white">
                                        {{ $emp['name'] }}
                                    </span>
                                    <span style="font-size: 10px; font-weight: 700; color: {{ $emp['is_live'] ? '#059669' : '#2563eb' }}; background: {{ $emp['is_live'] ? '#ecfdf5' : '#eff6ff' }}; padding: 1px 6px; border-radius: 4px; flex-shrink: 0;">
                                        {{ $emp['is_live'] ? 'Live GPS' : 'Check-in' }}
                                    </span>
                                </div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $emp['position'] }} • <strong style="color: #334155;" class="dark:text-slate-300">{{ $emp['principal'] }}</strong>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 10px; color: #94a3b8; margin-top: 3px;">
                                    <span>📍 {{ $emp['branch'] }}</span>
                                    <span>🕒 {{ $emp['last_update'] }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 40px 16px; color: #94a3b8;">
                            <div style="font-size: 32px; margin-bottom: 8px;">🗺️</div>
                            <div style="font-size: 13px; font-weight: 700; color: #475569;" class="dark:text-slate-300">Tidak ada karyawan yang cocok</div>
                            <div style="font-size: 11px; margin-top: 4px;">Belum ada karyawan yang sedang check-in sesuai kriteria filter saat ini.</div>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    {{-- Leaflet JS Core --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <script>
    (function () {
        let map = null;
        let markersGroup = null;
        let markersMap = {};
        window.currentSelectedEmpId = null;
        window.liveAttendanceData = @json($employees);
        window.liveAttendanceDataMap = {};

        let streetLayer = null;
        let satelliteLayer = null;
        let currentLayer = 'street';

        let autoRefreshIntervalId = null;
        let countdownTimerId = null;
        let secondsRemaining = 30;

        /**
         * Inisialisasi atau re-render Peta Leaflet
         */
        window.initOrUpdateMap = function () {
            const mapEl = document.getElementById('live-map-container');
            if (!mapEl) return;

            if (typeof L === 'undefined' || !L.map) {
                setTimeout(window.initOrUpdateMap, 100);
                return;
            }

            // Jika instance map lama ada tapi sudah tidak terpasang di DOM atau kontainer berubah
            if (map) {
                try {
                    if (!document.body.contains(mapEl) || map.getContainer() !== mapEl) {
                        map.remove();
                        map = null;
                        markersGroup = null;
                    }
                } catch (e) {
                    map = null;
                    markersGroup = null;
                }
            }

            if (!map) {
                // Bersihkan properti internal Leaflet pada DOM jika ada dari render sebelumnya
                if (mapEl._leaflet_id) {
                    delete mapEl._leaflet_id;
                }

                // Street tile
                streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                });

                // Satellite tile
                satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: '&copy; Esri World Imagery'
                });

                // Inisialisasi peta Leaflet (closePopupOnClick: false & tap: false agar tidak tertutup otomatis)
                map = L.map('live-map-container', {
                    center: [-7.5, 112.5],
                    zoom: 8,
                    layers: [streetLayer],
                    closePopupOnClick: false,
                    tap: false
                });

                // Layer group langsung bawaan Leaflet Core (100% reliable)
                markersGroup = L.featureGroup().addTo(map);

                setTimeout(function () {
                    if (map) map.invalidateSize();
                }, 200);

                // Layer toggle buttons
                const btnStreet = document.getElementById('btn-layer-street');
                const btnSatellite = document.getElementById('btn-layer-satellite');

                if (btnStreet && btnSatellite) {
                    btnStreet.addEventListener('click', function () {
                        if (currentLayer !== 'street') {
                            map.removeLayer(satelliteLayer);
                            map.addLayer(streetLayer);
                            currentLayer = 'street';
                            btnStreet.classList.add('active');
                            btnSatellite.classList.remove('active');
                        }
                    });

                    btnSatellite.addEventListener('click', function () {
                        if (currentLayer !== 'satellite') {
                            map.removeLayer(streetLayer);
                            map.addLayer(satelliteLayer);
                            currentLayer = 'satellite';
                            btnSatellite.classList.add('active');
                            btnStreet.classList.remove('active');
                        }
                    });
                }

                // Recenter all button (fokus ke seluruh karyawan check-in)
                const btnRecenter = document.getElementById('btn-recenter-all');
                const btnFitAction = document.getElementById('btn-fit-bounds-action');

                if (btnRecenter) btnRecenter.addEventListener('click', focusAllMarkers);
                if (btnFitAction) btnFitAction.addEventListener('click', focusAllMarkers);

                // Fullscreen toggle button
                const btnFullscreen = document.getElementById('btn-fullscreen-toggle');
                const mapCard = document.getElementById('map-viewport-card');

                if (btnFullscreen && mapCard) {
                    btnFullscreen.addEventListener('click', function () {
                        mapCard.classList.toggle('map-fullscreen-active');
                        const isFull = mapCard.classList.contains('map-fullscreen-active');
                        btnFullscreen.innerHTML = isFull ? '✕ Keluar Layar Penuh' : '⛶ Fullscreen';
                        setTimeout(function () {
                            map.invalidateSize();
                        }, 300);
                    });
                }

                // Auto-refresh switch logic
                const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
                const countdownEl = document.getElementById('refresh-countdown');

                function startAutoRefresh() {
                    secondsRemaining = 30;
                    if (countdownEl) {
                        countdownEl.style.display = 'inline';
                        countdownEl.textContent = '(' + secondsRemaining + 's)';
                    }

                    clearInterval(countdownTimerId);
                    countdownTimerId = setInterval(function () {
                        secondsRemaining--;
                        if (secondsRemaining <= 0) {
                            secondsRemaining = 30;
                            if (window.Livewire) {
                                @this.fetchLatestCoordinates();
                            }
                        }
                        if (countdownEl) countdownEl.textContent = '(' + secondsRemaining + 's)';
                    }, 1000);
                }

                function stopAutoRefresh() {
                    clearInterval(countdownTimerId);
                    if (countdownEl) countdownEl.style.display = 'none';
                }

                if (autoRefreshToggle) {
                    autoRefreshToggle.addEventListener('change', function () {
                        if (this.checked) {
                            startAutoRefresh();
                        } else {
                            stopAutoRefresh();
                        }
                    });
                }

                // Live server clock ticker
                const clockEl = document.getElementById('live-clock-display');
                setInterval(function () {
                    if (clockEl) {
                        const now = new Date();
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        const seconds = String(now.getSeconds()).padStart(2, '0');
                        clockEl.textContent = hours + ':' + minutes + ':' + seconds + ' WIB';
                    }
                }, 1000);
            }

            // Render seluruh titik karyawan yang sedang check-in
            renderMarkers(window.liveAttendanceData || []);
        };

        /**
         * Render marker kustom untuk setiap karyawan dan langsung FOKUS ke area karyawan
         */
        function renderMarkers(employees) {
            if (!map) return;

            if (!markersGroup) {
                markersGroup = L.featureGroup().addTo(map);
            } else {
                markersGroup.clearLayers();
            }

            markersMap = {};
            window.liveAttendanceDataMap = {};

            if (!employees || employees.length === 0) {
                map.setView([-7.2575, 112.7521], 8);
                window.closeEmployeeDetail();
                return;
            }

            const latlngs = [];

            employees.forEach(function (emp) {
                window.liveAttendanceDataMap[emp.id] = emp;

                if (!emp.lat || !emp.lng) return;

                const lat = parseFloat(emp.lat);
                const lng = parseFloat(emp.lng);

                if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;

                const markerIcon = createEmployeePinIcon(emp);
                const marker = L.marker([lat, lng], { 
                    icon: markerIcon,
                    title: `${emp.name} (${emp.principal} • ${emp.branch})`,
                    riseOnHover: true
                });

                // Klik Marker: Tampilkan Floating Detail Panel (Bukan Leaflet popup bawaan)
                marker.on('click', function (e) {
                    if (e && e.originalEvent) {
                        L.DomEvent.stopPropagation(e.originalEvent);
                    }
                    window.showEmployeeDetail(emp.id, false);
                });

                // Matikan propagasi klik pada elemen DOM icon marker
                marker.on('add', function () {
                    const el = marker.getElement();
                    if (el) {
                        L.DomEvent.disableClickPropagation(el);
                    }
                });

                markersGroup.addLayer(marker);
                markersMap[emp.id] = marker;
                latlngs.push([lat, lng]);
            });

            // Jika sebelumnya ada karyawan yang sedang dilihat detailnya, pertahankan tampilan
            if (window.currentSelectedEmpId && window.liveAttendanceDataMap[window.currentSelectedEmpId]) {
                window.showEmployeeDetail(window.currentSelectedEmpId, false);
            } else if (latlngs.length === 1) {
                // FOKUSKAN PETA LANGSUNG KE SELURUH KARYAWAN YANG SEDANG CHECK-IN
                map.setView(latlngs[0], 16);
            } else if (latlngs.length > 1) {
                map.invalidateSize();
                const bounds = L.latLngBounds(latlngs);
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });
                }
            }
        }

        /**
         * Tombol fokus ke seluruh marker aktif
         */
        function focusAllMarkers() {
            if (!map || !markersGroup) return;
            const bounds = markersGroup.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });
            } else {
                map.setView([-7.2575, 112.7521], 8);
            }
        }

        /**
         * Membuat HTML DivIcon khusus foto profile atau inisial nama
         */
        function createEmployeePinIcon(emp) {
            const hasPhoto = emp.photo_url && emp.photo_url.trim() !== '';
            const isLive = emp.source === 'live_tracking';
            const borderColor = isLive ? '#10b981' : '#3b82f6';

            const avatarContent = hasPhoto
                ? `<div class="emp-marker-avatar">
                     <img src="${emp.photo_url}" alt="${emp.name}" onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\\'emp-initials\\' style=\\'background:${emp.avatar_color};\\'>${emp.initials}</span>';" />
                   </div>`
                : `<div class="emp-marker-avatar">
                     <span class="emp-initials" style="background:${emp.avatar_color};">${emp.initials}</span>
                   </div>`;

            const badgeHtml = isLive
                ? `<span class="emp-live-pulse" title="GPS Live Tracking Aktif"></span>`
                : `<span class="emp-checkin-badge" title="Titik Presensi Check-in"></span>`;

            const isCurrentlySelected = window.currentSelectedEmpId && String(window.currentSelectedEmpId) === String(emp.id);

            const iconHtml = `
                <div class="emp-map-pin ${isLive ? 'is-live-tracking' : ''} ${isCurrentlySelected ? 'is-active-selected' : ''}" data-emp-id="${emp.id}">
                    <div class="emp-pin-disc" style="border-color: ${borderColor};">
                        ${avatarContent}
                        ${badgeHtml}
                    </div>
                    <div class="emp-pin-arrow" style="border-top-color: ${borderColor};"></div>
                    <div class="emp-pin-shadow"></div>
                </div>
            `;

            return L.divIcon({
                className: 'leaflet-emp-custom-icon',
                html: iconHtml,
                iconSize: [46, 56],
                iconAnchor: [23, 54],
                popupAnchor: [0, -50]
            });
        }

        /**
         * Menampilkan Floating Detail Panel untuk karyawan yang dipilih (100% Persisten & Murni Ditutup Manual)
         * @param {number|string} empId
         * @param {boolean} shouldPan - apakah kamera peta perlu digeser ke titik karyawan
         */
        window.showEmployeeDetail = function(empId, shouldPan = true) {
            const emp = window.liveAttendanceDataMap ? window.liveAttendanceDataMap[empId] : null;
            if (!emp) return;

            window.currentSelectedEmpId = empId;

            // 1. Highlight pin marker di peta
            document.querySelectorAll('.emp-map-pin').forEach(function(el) {
                el.classList.remove('is-active-selected');
            });
            const activePinEl = document.querySelector(`.emp-map-pin[data-emp-id="${empId}"]`);
            if (activePinEl) {
                activePinEl.classList.add('is-active-selected');
            }

            // 2. Highlight item di sidebar
            document.querySelectorAll('.emp-list-item').forEach(function(el) {
                el.classList.remove('is-selected');
            });
            const activeSideItem = document.querySelector(`.emp-list-item[data-emp-id="${empId}"]`);
            if (activeSideItem) {
                activeSideItem.classList.add('is-selected');
                activeSideItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // 3. Pusatkan kamera peta jika diminta
            if (shouldPan && map && emp.lat && emp.lng) {
                const lat = parseFloat(emp.lat);
                const lng = parseFloat(emp.lng);
                if (!isNaN(lat) && !isNaN(lng)) {
                    map.panTo([lat, lng], { animate: true, duration: 0.5 });
                }
            }

            // 4. Render isi HTML Floating Detail Panel
            const panel = document.getElementById('emp-floating-detail-panel');
            if (panel) {
                panel.innerHTML = renderFloatingDetailHtml(emp);
                panel.style.display = 'flex';
            }
        };

        /**
         * Menutup Floating Detail Panel secara manual
         */
        window.closeEmployeeDetail = function() {
            window.currentSelectedEmpId = null;

            const panel = document.getElementById('emp-floating-detail-panel');
            if (panel) {
                panel.style.display = 'none';
                panel.innerHTML = '';
            }

            document.querySelectorAll('.emp-map-pin').forEach(function(el) {
                el.classList.remove('is-active-selected');
            });
            document.querySelectorAll('.emp-list-item').forEach(function(el) {
                el.classList.remove('is-selected');
            });
        };

        // Alias pengaman untuk kompatibilitas
        window.closeActivePopup = window.closeEmployeeDetail;

        /**
         * Render HTML isi dari Floating Detail Panel
         */
        function renderFloatingDetailHtml(emp) {
            const isLive = emp.source === 'live_tracking';
            const headerClass = isLive ? 'panel-header-live' : 'panel-header-checkin';
            const sourceBadgeText = isLive ? '● LIVE GPS TRACKING' : '📌 TITIK CHECK-IN';
            const sourceBadgeBg = isLive ? 'rgba(16, 185, 129, 0.3)' : 'rgba(59, 130, 246, 0.3)';

            const photoHtml = (emp.photo_url && emp.photo_url.trim() !== '')
                ? `<img src="${emp.photo_url}" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2.5px solid #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.25); flex-shrink: 0;" onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\\'width:52px;height:52px;border-radius:50%;background:${emp.avatar_color};color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;border:2.5px solid #ffffff;box-shadow:0 4px 10px rgba(0,0,0,0.25);flex-shrink:0;\\'>${emp.initials}</div>';" />`
                : `<div style="width: 52px; height: 52px; border-radius: 50%; background: ${emp.avatar_color}; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; border: 2.5px solid #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.25); flex-shrink: 0;">${emp.initials}</div>`;

            const latNum = Number(emp.lat).toFixed(6);
            const lngNum = Number(emp.lng).toFixed(6);

            return `
                <div class="${headerClass}">
                    <button 
                        type="button" 
                        class="panel-close-btn" 
                        onclick="window.closeEmployeeDetail()" 
                        title="Tutup Kartu Info (Esc)"
                    >
                        ✕
                    </button>

                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding-right: 36px;">
                        <span style="background: ${sourceBadgeBg}; border: 1px solid rgba(255,255,255,0.35); padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; letter-spacing: 0.04em;">
                            ${sourceBadgeText}
                        </span>
                        <span style="font-size: 11px; opacity: 0.9; font-weight: 600;">
                            ${emp.last_update_diff}
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 14px;">
                        ${photoHtml}
                        <div style="min-width: 0; flex: 1;">
                            <div style="font-weight: 800; font-size: 15px; line-height: 1.3; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${emp.name}">
                                ${emp.name}
                            </div>
                            <div style="font-size: 11px; opacity: 0.85; margin-top: 1px;">
                                NIK: <strong>${emp.employee_no}</strong>
                            </div>
                            <div style="font-size: 11px; font-weight: 600; opacity: 0.95; margin-top: 2px;">
                                ${emp.position}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel-body-content">
                    {{-- Prinsiple & Area Badges --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px;" class="dark:bg-slate-800 dark:border-slate-700">
                            <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Prinsiple</div>
                            <div style="font-size: 12px; font-weight: 800; color: #0f172a; margin-top: 2px;" class="dark:text-white">${emp.principal}</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px;" class="dark:bg-slate-800 dark:border-slate-700">
                            <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Area / Cabang</div>
                            <div style="font-size: 12px; font-weight: 800; color: #0f172a; margin-top: 2px;" class="dark:text-white">${emp.branch}</div>
                        </div>
                    </div>

                    {{-- Data Waktu & Lokasi --}}
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12px; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 12px; padding: 12px;" class="dark:bg-slate-800/60 dark:border-slate-700">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 11px;">🕒 Jam Check-in:</span>
                            <strong style="color: #0f172a; font-size: 12px;" class="dark:text-white">${emp.checkin_time}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-size: 11px;">⏱️ Waktu Koordinat:</span>
                            <strong style="color: #0f172a; font-size: 12px;" class="dark:text-white">${emp.last_update}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 11px;">📍 Titik GPS:</span>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="font-family: monospace; font-size: 11px; color: #334155; font-weight: 700;" class="dark:text-slate-300">
                                    ${latNum}, ${lngNum}
                                </span>
                                <button 
                                    type="button" 
                                    onclick="window.copyCoordinates('${emp.lat}', '${emp.lng}', this)"
                                    style="padding: 2px 7px; font-size: 10px; font-weight: 700; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; cursor: pointer; transition: all 0.15s ease;"
                                    title="Salin Koordinat GPS"
                                >
                                    📋 Salin
                                </button>
                            </div>
                        </div>

                        ${emp.location_name && emp.location_name !== '-' ? `
                            <div style="margin-top: 4px; padding-top: 8px; border-top: 1px dashed #e2e8f0;" class="dark:border-slate-700">
                                <span style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase; display: block;">Lokasi / Alamat Toko:</span>
                                <span style="color: #1e293b; font-size: 12px; font-weight: 600; line-height: 1.4; display: block; margin-top: 2px;" class="dark:text-slate-200">
                                    ${emp.location_name}
                                </span>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <div class="panel-action-bar">
                    <a 
                        href="${emp.google_maps_url}" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        style="flex: 1; padding: 9px 12px; background: #2563eb; color: #ffffff; text-align: center; border-radius: 10px; font-size: 11px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 5px; box-shadow: 0 2px 6px rgba(37,99,235,0.3); transition: background 0.15s ease;"
                    >
                        🗺️ Maps ↗
                    </a>
                    <a 
                        href="${emp.tracking_url}" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        style="flex: 1; padding: 9px 12px; background: #f1f5f9; color: #334155; text-align: center; border-radius: 10px; font-size: 11px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 5px; border: 1px solid #e2e8f0; transition: background 0.15s ease;"
                        class="dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200"
                    >
                        🛣️ Rute ↗
                    </a>
                    <button 
                        type="button"
                        onclick="window.closeEmployeeDetail()"
                        style="padding: 9px 14px; background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; border-radius: 10px; font-size: 11px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 4px; transition: all 0.15s ease;"
                        onmouseover="this.style.background='#fecaca'"
                        onmouseout="this.style.background='#fee2e2'"
                        title="Tutup info detail karyawan secara manual"
                    >
                        ✕ Tutup
                    </button>
                </div>
            `;
        }

        /**
         * Mengarahkan kamera peta ke titik karyawan saat nama diklik di sidebar
         */
        window.focusEmployeeOnMap = function(empId) {
            window.showEmployeeDetail(empId, true);
        };

        /**
         * Fitur salin koordinat ke clipboard
         */
        window.copyCoordinates = function(lat, lng, btnEl) {
            const coordText = `${lat}, ${lng}`;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(coordText).then(function() {
                    if (btnEl) {
                        const originalText = btnEl.innerHTML;
                        btnEl.innerHTML = '✓ Tersalin';
                        btnEl.style.background = '#dcfce7';
                        btnEl.style.color = '#15803d';
                        setTimeout(function() {
                            btnEl.innerHTML = originalText;
                            btnEl.style.background = '#e2e8f0';
                            btnEl.style.color = '#334155';
                        }, 2000);
                    }
                }).catch(function() {
                    prompt('Salin koordinat:', coordText);
                });
            } else {
                prompt('Salin koordinat:', coordText);
            }
        };

        // Tutup panel detail jika tombol ESC ditekan
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                window.closeEmployeeDetail();
            }
        });

        // Initialize on all possible events
        document.addEventListener('DOMContentLoaded', window.initOrUpdateMap);
        document.addEventListener('livewire:navigated', window.initOrUpdateMap);
        document.addEventListener('livewire:initialized', function () {
            window.initOrUpdateMap();

            Livewire.on('map-data-updated', function (payload) {
                const data = Array.isArray(payload) ? payload[0] : payload;
                if (!data) return;

                if (data.employees) {
                    window.liveAttendanceData = data.employees;
                    renderMarkers(data.employees);
                }

                // Update KPI text
                if (data.summary) {
                    const elCheckin = document.getElementById('kpi-total-checkedin');
                    const elLive = document.getElementById('kpi-total-live');
                    const elPt = document.getElementById('kpi-total-checkin');
                    const elCoverage = document.getElementById('kpi-coverage');
                    const elBadge = document.getElementById('sidebar-count-badge');

                    if (elCheckin) elCheckin.textContent = data.summary.total_checked_in;
                    if (elLive) elLive.textContent = data.summary.total_live_gps;
                    if (elPt) elPt.textContent = data.summary.total_checkin_point;
                    if (elCoverage) elCoverage.innerHTML = `${data.summary.active_principals_count} <span style="font-size: 14px; font-weight: 600; color: #64748b;">Prinsiple</span>`;
                    if (elBadge) elBadge.textContent = (data.employees ? data.employees.length : 0) + ' Titik';
                }
            });
        });

        // Window resize handler
        window.addEventListener('resize', function () {
            if (map) {
                map.invalidateSize();
            }
        });

    })();
    </script>
</x-filament-panels::page>
