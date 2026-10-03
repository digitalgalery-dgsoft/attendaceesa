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

        /* ─── Leaflet Popup Custom Styling ─── */
        .leaflet-popup-content-wrapper {
            padding: 0 !important;
            border-radius: 16px !important;
            overflow: hidden !important;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
        }
        .leaflet-popup-content {
            margin: 0 !important;
            width: 310px !important;
            line-height: 1.4 !important;
        }
        .popup-header {
            padding: 16px;
            color: #ffffff;
            position: relative;
        }
        .popup-header.live-bg {
            background: linear-gradient(135deg, #065f46 0%, #047857 100%);
        }
        .popup-header.checkin-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        }
        .popup-body {
            padding: 16px;
            background: #ffffff;
            color: #1e293b;
            font-size: 12px;
        }
        .popup-actions {
            padding: 12px 16px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
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
        window.liveAttendanceData = @json($employees);

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

                // Inisialisasi peta Leaflet
                map = L.map('live-map-container', {
                    center: [-7.5, 112.5],
                    zoom: 8,
                    layers: [streetLayer]
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

            if (!employees || employees.length === 0) {
                map.setView([-7.2575, 112.7521], 8);
                return;
            }

            const latlngs = [];

            employees.forEach(function (emp) {
                if (!emp.lat || !emp.lng) return;

                const lat = parseFloat(emp.lat);
                const lng = parseFloat(emp.lng);

                if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;

                const markerIcon = createEmployeePinIcon(emp);
                const marker = L.marker([lat, lng], { 
                    icon: markerIcon,
                    title: emp.name,
                    riseOnHover: true
                });

                // Popup Template
                const popupContent = createPopupHtml(emp);
                marker.bindPopup(popupContent, { maxWidth: 320, offset: [0, -30] });

                // Tooltip on hover
                marker.bindTooltip(`<strong>${emp.name}</strong><br><span style="font-size:11px;color:#64748b;">${emp.principal} • ${emp.branch}</span>`, {
                    direction: 'top',
                    offset: [0, -45]
                });

                markersGroup.addLayer(marker);
                markersMap[emp.id] = marker;
                latlngs.push([lat, lng]);
            });

            // FOKUSKAN PETA LANGSUNG KE SELURUH KARYAWAN YANG SEDANG CHECK-IN
            if (latlngs.length === 1) {
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

            const iconHtml = `
                <div class="emp-map-pin ${isLive ? 'is-live-tracking' : ''}" data-emp-id="${emp.id}">
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
         * Membuat isi popup detail karyawan
         */
        function createPopupHtml(emp) {
            const isLive = emp.source === 'live_tracking';
            const headerClass = isLive ? 'live-bg' : 'checkin-bg';
            const sourceBadge = isLive 
                ? `<span style="background: rgba(255,255,255,0.25); padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700;">● GPS LIVE TRACKING</span>`
                : `<span style="background: rgba(255,255,255,0.25); padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700;">📌 TITIK CHECK-IN</span>`;

            const photoHtml = emp.photo_url
                ? `<img src="${emp.photo_url}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #ffffff;" onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\\'width:48px;height:48px;border-radius:50%;background:${emp.avatar_color};color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;border:2px solid #ffffff;\\'>${emp.initials}</div>';" />`
                : `<div style="width:48px;height:48px;border-radius:50%;background:${emp.avatar_color};color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;border:2px solid #ffffff;">${emp.initials}</div>`;

            return `
                <div style="font-family: inherit;">
                    <div class="popup-header ${headerClass}">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            ${sourceBadge}
                            <span style="font-size: 11px; opacity: 0.9;">${emp.last_update_diff}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            ${photoHtml}
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-weight: 800; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${emp.name}</div>
                                <div style="font-size: 11px; opacity: 0.9;">NIK: ${emp.employee_no}</div>
                                <div style="font-size: 11px; font-weight: 600; opacity: 0.95;">${emp.position}</div>
                            </div>
                        </div>
                    </div>
                    <div class="popup-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px; background: #f8fafc; padding: 10px; border-radius: 8px;">
                            <div>
                                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Prinsiple</div>
                                <div style="font-size: 12px; font-weight: 700; color: #0f172a;">${emp.principal}</div>
                            </div>
                            <div>
                                <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Area / Cabang</div>
                                <div style="font-size: 12px; font-weight: 700; color: #0f172a;">${emp.branch}</div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px; font-size: 11px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #64748b;">🕒 Jam Check-in:</span>
                                <strong style="color: #0f172a;">${emp.checkin_time}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #64748b;">⏱️ Waktu Koordinat:</span>
                                <strong style="color: #0f172a;">${emp.last_update}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #64748b;">📍 Koordinat:</span>
                                <span style="font-family: monospace; font-size: 10px; color: #334155;">${Number(emp.lat).toFixed(6)}, ${Number(emp.lng).toFixed(6)}</span>
                            </div>
                            ${emp.location_name && emp.location_name !== '-' ? `
                                <div style="margin-top: 4px; padding-top: 6px; border-top: 1px dashed #e2e8f0;">
                                    <span style="color: #64748b; font-size: 10px; display: block;">Lokasi / Alamat:</span>
                                    <span style="color: #1e293b; font-size: 11px;">${emp.location_name}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="popup-actions">
                        <a 
                            href="${emp.google_maps_url}" 
                            target="_blank" 
                            style="flex: 1; padding: 7px 10px; background: #2563eb; color: #ffffff; text-align: center; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 4px;"
                        >
                            Google Maps ↗
                        </a>
                        <a 
                            href="${emp.tracking_url}" 
                            target="_blank" 
                            style="flex: 1; padding: 7px 10px; background: #f1f5f9; color: #334155; text-align: center; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 4px;"
                        >
                            Riwayat Rute ↗
                        </a>
                    </div>
                </div>
            `;
        }

        /**
         * Mengarahkan kamera peta ke titik karyawan saat nama diklik di sidebar
         */
        window.focusEmployeeOnMap = function(empId) {
            const marker = markersMap[empId];
            if (marker && map) {
                map.flyTo(marker.getLatLng(), 17, { duration: 1.2 });
                marker.openPopup();
            }
        };

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
