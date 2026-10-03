<x-filament-panels::page>
@php
    $mapData = $this->getMapData();
    $employees = $mapData['employees'] ?? [];
    $summary = $mapData['summary'] ?? [];
    $unmapped = $mapData['unmapped'] ?? [];
    $allPrincipals = $allPrincipals ?? \App\Models\Principal::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    $allBranches = $allBranches ?? \App\Models\Branch::orderBy('name')->get(['id', 'name']);
@endphp

    {{-- Leaflet Core CSS & MarkerCluster CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin=""/>

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
        }
        .dark .filter-panel {
            background: #1e293b;
            border-color: #334155;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 12px;
        }
        @media (min-width: 640px) {
            .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .filter-grid { grid-template-columns: 2fr 2fr 1.5fr 2.5fr auto; align-items: flex-end; }
        }

        .custom-select, .custom-input {
            width: 100%;
            padding: 9px 14px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #0f172a;
            outline: none;
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
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            padding: 6px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .dark .map-floating-toolbar {
            background: rgba(15, 23, 42, 0.92);
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
        .leaflet-emp-custom-icon {
            background: transparent !important;
            border: none !important;
        }

        .emp-map-pin {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .emp-map-pin:hover {
            transform: scale(1.18) translateY(-4px);
            z-index: 99999 !important;
        }

        .emp-pin-disc {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 3px solid;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .emp-marker-avatar {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
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
            border-top: 8px solid;
            margin: 0 auto;
            filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.25));
        }

        .emp-pin-shadow {
            width: 18px;
            height: 5px;
            background: rgba(0, 0, 0, 0.25);
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

        /* Custom Cluster Badge */
        .marker-cluster-small {
            background-color: rgba(16, 185, 129, 0.5) !important;
        }
        .marker-cluster-small div {
            background-color: rgba(16, 185, 129, 0.9) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
        }
        .marker-cluster-medium {
            background-color: rgba(59, 130, 246, 0.5) !important;
        }
        .marker-cluster-medium div {
            background-color: rgba(59, 130, 246, 0.9) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
        }
    </style>

    <div class="live-map-wrapper" id="live-map-wrapper">
        
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
                    Waktu Server: <strong style="color: #ffffff; font-family: monospace;" id="live-clock-display">{{ $summary['timestamp'] }}</strong>
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
                        {{ $summary['total_checked_in'] }}
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
                        {{ $summary['total_live_gps'] }}
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
                        {{ $summary['total_checkin_point'] }}
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
                        {{ $summary['active_principals_count'] }} <span style="font-size: 14px; font-weight: 600; color: #64748b;">Prinsiple</span>
                    </div>
                    <span style="font-size: 11px; color: #7c3aed; font-weight: 600;">{{ $summary['active_branches_count'] }} Area / Cabang Aktif</span>
                </div>
                <div class="kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 3. Filter Bar (Prinsiple, Area, Tipe Titik, Pencarian) --}}
        <div class="filter-panel">
            <div class="filter-grid">
                {{-- Filter Prinsiple --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #334155;" class="dark:text-slate-300">
                        🏢 Filter Prinsiple
                    </label>
                    <select wire:model.live="selectedPrincipalId" class="custom-select">
                        <option value="">-- Semua Prinsiple --</option>
                        @foreach ($allPrincipals as $p)
                            <option value="{{ (string)$p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Area / Cabang --}}
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #334155;" class="dark:text-slate-300">
                        📍 Filter Area / Cabang
                    </label>
                    <select wire:model.live="selectedBranchId" class="custom-select">
                        <option value="">-- Semua Area / Cabang --</option>
                        @foreach ($allBranches as $b)
                            <option value="{{ (string)$b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Tipe Titik --}}
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

                {{-- Pencarian Cepat Nama / NIK --}}
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

                {{-- Reset Filter Button --}}
                <div>
                    <button 
                        type="button" 
                        wire:click="resetFilters" 
                        style="width: 100%; height: 38px; padding: 0 16px; font-size: 12px; font-weight: 700; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.15s ease;"
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
                    <button type="button" class="toolbar-btn" id="btn-recenter-all" title="Pusatkan Peta ke Seluruh Karyawan">
                        🎯 Pusatkan
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
                            onclick="focusEmployeeOnMap({{ $emp['id'] }})"
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

    {{-- Leaflet JS & MarkerCluster JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin=""></script>

    <script>
    (function () {
        let map = null;
        let clusterGroup = null;
        let markersMap = {};
        let initialData = @json($employees);
        let summaryData = @json($summary);

        let streetLayer = null;
        let satelliteLayer = null;
        let currentLayer = 'street';

        let autoRefreshIntervalId = null;
        let countdownTimerId = null;
        let secondsRemaining = 30;

        function initMap() {
            if (map !== null) return;

            const mapEl = document.getElementById('live-map-container');
            if (!mapEl) return;

            // Street tile (OpenStreetMap)
            streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            });

            // Satellite tile (Esri World Imagery)
            satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: '&copy; Esri &mdash; World Imagery'
            });

            // Initialize Leaflet centered around Indonesia archipelago
            map = L.map('live-map-container', {
                center: [-2.5489, 118.0149],
                zoom: 5,
                layers: [streetLayer]
            });

            // Initialize MarkerClusterGroup
            clusterGroup = L.markerClusterGroup({
                maxClusterRadius: 40,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                iconCreateFunction: function(cluster) {
                    const count = cluster.getChildCount();
                    let cClass = 'marker-cluster-small';
                    if (count > 10) cClass = 'marker-cluster-medium';
                    if (count > 30) cClass = 'marker-cluster-large';

                    return L.divIcon({
                        html: '<div><span>' + count + '</span></div>',
                        className: 'marker-cluster ' + cClass,
                        iconSize: L.point(40, 40)
                    });
                }
            });

            map.addLayer(clusterGroup);

            // Render initial data
            renderMarkers(initialData);

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

            // Recenter all button
            const btnRecenter = document.getElementById('btn-recenter-all');
            const btnFitAction = document.getElementById('btn-fit-bounds-action');

            function fitBoundsAction() {
                if (clusterGroup && clusterGroup.getLayers().length > 0) {
                    map.fitBounds(clusterGroup.getBounds(), { padding: [50, 50], maxZoom: 16 });
                } else {
                    map.setView([-2.5489, 118.0149], 5);
                }
            }

            if (btnRecenter) btnRecenter.addEventListener('click', fitBoundsAction);
            if (btnFitAction) btnFitAction.addEventListener('click', fitBoundsAction);

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

            // Update live clock every second
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

        /**
         * Render marker kustom untuk setiap karyawan
         */
        function renderMarkers(employees) {
            if (!clusterGroup) return;

            clusterGroup.clearLayers();
            markersMap = {};

            if (!employees || employees.length === 0) return;

            const latlngs = [];

            employees.forEach(function (emp) {
                if (!emp.lat || !emp.lng) return;

                const lat = parseFloat(emp.lat);
                const lng = parseFloat(emp.lng);

                const markerIcon = createEmployeePinIcon(emp);
                const marker = L.marker([lat, lng], { icon: markerIcon });

                // Popup Template
                const popupContent = createPopupHtml(emp);
                marker.bindPopup(popupContent, { maxWidth: 320 });

                // Tooltip on hover
                marker.bindTooltip(`<strong>${emp.name}</strong><br><span style="font-size:11px;color:#64748b;">${emp.principal} • ${emp.position}</span>`, {
                    direction: 'top',
                    offset: [0, -48]
                });

                clusterGroup.addLayer(marker);
                markersMap[emp.id] = marker;
                latlngs.push([lat, lng]);
            });

            if (latlngs.length > 0 && map) {
                const bounds = L.latLngBounds(latlngs);
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
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
         * Global click handler untuk item di daftar sidebar
         */
        window.focusEmployeeOnMap = function(empId) {
            const marker = markersMap[empId];
            if (marker && map) {
                if (clusterGroup && clusterGroup.hasLayer(marker)) {
                    clusterGroup.zoomToShowLayer(marker, function () {
                        marker.openPopup();
                    });
                } else {
                    map.flyTo(marker.getLatLng(), 17, { duration: 1.2 });
                    marker.openPopup();
                }
            }
        };

        // Initialize on DOM load
        document.addEventListener('DOMContentLoaded', function () {
            initMap();
        });

        // Re-initialize or update on Livewire event
        document.addEventListener('livewire:initialized', function () {
            initMap();

            Livewire.on('map-data-updated', function (payload) {
                const data = Array.isArray(payload) ? payload[0] : payload;
                if (!data) return;

                if (data.employees) {
                    renderMarkers(data.employees);
                }

                // Update KPI text jika ada
                if (data.summary) {
                    const elCheckin = document.getElementById('kpi-total-checkedin');
                    const elLive = document.getElementById('kpi-total-live');
                    const elPt = document.getElementById('kpi-total-checkin');
                    const elBadge = document.getElementById('sidebar-count-badge');

                    if (elCheckin) elCheckin.textContent = data.summary.total_checked_in;
                    if (elLive) elLive.textContent = data.summary.total_live_gps;
                    if (elPt) elPt.textContent = data.summary.total_checkin_point;
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
