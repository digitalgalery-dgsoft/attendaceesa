<x-filament-panels::page>
    <style>
        .wg-wizard-wrapper {
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: 100%;
        }

        /* STEPPER HEADER */
        .stepper-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 32px;
            padding: 16px 24px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .dark .stepper-container {
            background: #1e293b;
            border-color: #334155;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
        }
        .step-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 15px;
            transition: all 0.2s ease;
        }
        .step-badge.active {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
        }
        .step-badge.inactive {
            background: #f1f5f9;
            color: #94a3b8;
        }
        .dark .step-badge.inactive {
            background: #334155;
            color: #64748b;
        }

        .step-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }
        .dark .step-title {
            color: #f8fafc;
        }
        .step-title.inactive {
            color: #64748b;
        }
        .dark .step-title.inactive {
            color: #94a3b8;
        }

        .step-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 1px;
        }
        .dark .step-subtitle {
            color: #94a3b8;
        }

        .stepper-divider {
            width: 48px;
            height: 2px;
            background: #e2e8f0;
        }
        .dark .stepper-divider {
            background: #334155;
        }

        /* CARDS & GRID */
        .wizard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        @media (min-width: 1024px) {
            .wizard-grid {
                grid-template-columns: 380px 1fr;
            }
        }

        .wizard-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .dark .wizard-card {
            background: #1e293b;
            border-color: #334155;
        }

        .wizard-card-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.2px;
        }
        .dark .wizard-card-title {
            color: #f8fafc;
        }

        /* FORM ELEMENTS */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .dark .form-label {
            color: #cbd5e1;
        }
        .required-star {
            color: #ef4444;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 10px 14px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #0f172a;
            transition: all 0.15s ease;
            outline: none;
        }
        .dark .form-input, .dark .form-select {
            background: #0f172a;
            border-color: #475569;
            color: #f8fafc;
        }
        .form-input:focus, .form-select:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .info-box-note {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12px;
            color: #0369a1;
            line-height: 1.5;
        }
        .dark .info-box-note {
            background: #082f49;
            border-color: #0369a1;
            color: #bae6fd;
        }

        .info-box-general {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 12px;
            color: #475569;
            text-align: center;
            font-weight: 500;
        }
        .dark .info-box-general {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
        }

        /* TOGGLE SWITCHES */
        .toggle-switch-wrapper {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .toggle-switch-wrapper input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .25s ease;
            border-radius: 24px;
        }
        .dark .toggle-slider {
            background-color: #475569;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .25s ease;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        input:checked + .toggle-slider {
            background-color: #0284c7;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }

        /* DAYS LIST */
        .days-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
        }
        .days-quick-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-pill-action {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }
        .btn-pill-green {
            background: #dcfce7;
            color: #15803d;
            border-color: #bbf7d0;
        }
        .btn-pill-green:hover {
            background: #bbf7d0;
        }
        .btn-pill-blue {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }
        .btn-pill-blue:hover {
            background: #bae6fd;
        }

        .day-row-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        .dark .day-row-card {
            border-color: #334155;
            background: #0f172a;
        }
        .day-row-card.active-day {
            border-color: #93c5fd;
        }
        .dark .day-row-card.active-day {
            border-color: #1e3a8a;
        }

        .day-row-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
        }
        .day-row-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .day-name-label {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .dark .day-name-label {
            color: #f8fafc;
        }
        .day-name-label.inactive {
            color: #94a3b8;
        }

        .day-row-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .custom-option-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }
        .dark .custom-option-label {
            color: #94a3b8;
        }

        .custom-option-body {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 16px;
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 12px;
        }
        @media (min-width: 768px) {
            .custom-option-body {
                grid-template-columns: 2fr 1fr 2fr;
            }
        }
        .dark .custom-option-body {
            background: #182234;
            border-top-color: #334155;
        }

        /* TABLE STEP 2 */
        .step2-table-container {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
        }
        .dark .step2-table-container {
            border-color: #334155;
            background: #0f172a;
        }

        .step2-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        .step2-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .step2-table th {
            background: #1e293b;
            color: #94a3b8;
            border-bottom-color: #334155;
        }
        .step2-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .dark .step2-table td {
            border-bottom-color: #334155;
        }
        .step2-table tr:hover {
            background: #f8fafc;
        }
        .dark .step2-table tr:hover {
            background: #182234;
        }

        /* ACTION BUTTONS */
        .wizard-footer-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            margin-top: 8px;
        }
        .dark .wizard-footer-actions {
            border-top-color: #334155;
        }

        .btn-wizard {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
            outline: none;
        }
        .btn-wizard-primary {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }
        .btn-wizard-primary:hover {
            background: #0369a1;
            transform: translateY(-1px);
        }
        .btn-wizard-success {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        .btn-wizard-success:hover {
            background: #047857;
            transform: translateY(-1px);
        }
        .btn-wizard-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .dark .btn-wizard-secondary {
            background: #334155;
            color: #f1f5f9;
            border-color: #475569;
        }
        .btn-wizard-secondary:hover {
            background: #e2e8f0;
        }
        .dark .btn-wizard-secondary:hover {
            background: #475569;
        }
    </style>

    @php
        $viewData = $this->getViewData();
        $shifts = $viewData['shifts'];
        $branches = $viewData['branches'];
        $principals = $viewData['principals'];
        $workLocations = $viewData['workLocations'];
        $availableEmployees = $viewData['availableEmployees'];
        $selectedEmployees = $viewData['selectedEmployees'];
        $totalSelected = $viewData['totalSelected'];
        $pagination = $viewData['pagination'];
    @endphp

    <div class="wg-wizard-wrapper">
        {{-- STEPPER HEADER --}}
        <div class="stepper-container">
            <div class="step-item" wire:click="goToStep1">
                <div class="step-badge {{ $currentStep === 1 ? 'active' : 'inactive' }}">
                    1
                </div>
                <div>
                    <div class="step-title {{ $currentStep === 1 ? '' : 'inactive' }}">Description & Configuration</div>
                    <div class="step-subtitle">Adding title, setting working days</div>
                </div>
            </div>

            <div class="stepper-divider"></div>

            <div class="step-item" wire:click="goToStep2">
                <div class="step-badge {{ $currentStep === 2 ? 'active' : 'inactive' }}">
                    2
                </div>
                <div>
                    <div class="step-title {{ $currentStep === 2 ? '' : 'inactive' }}">Implementing Working Group</div>
                    <div class="step-subtitle">Set rules to roles & employees</div>
                </div>
            </div>
        </div>

        {{-- STEP 1: DESCRIPTION & CONFIGURATION --}}
        @if ($currentStep === 1)
            <div class="wizard-grid">
                {{-- LEFT: DESCRIPTION --}}
                <div class="wizard-card">
                    <div class="wizard-card-title">Description</div>

                    <div class="form-group">
                        <label class="form-label">
                            Name <span class="required-star">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="name"
                            placeholder="Please enter Name here"
                            class="form-input"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Date Applied <span class="required-star">*</span>
                        </label>
                        <input
                            type="date"
                            wire:model="data_applied_date"
                            class="form-input"
                            required
                        />
                    </div>

                    <div class="info-box-note">
                        <div style="font-weight: 700; margin-bottom: 2px;">Informasi Sistem:</div>
                        Working group will applied from date applied afterward. This feature will be generating alpha to absent employees.
                    </div>

                    <div class="form-group">
                        <label class="form-label">For Area (Cabang)</label>
                        <select wire:model="branch_id" class="form-select">
                            <option value="">-- Select Area --</option>
                            @foreach ($branches as $id => $branchName)
                                <option value="{{ $id }}">{{ $branchName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prinsiple (Opsional)</label>
                        <select wire:model="principal_id" class="form-select">
                            <option value="">-- Semua Prinsiple --</option>
                            @foreach ($principals as $id => $principalName)
                                <option value="{{ $id }}">{{ $principalName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- RIGHT: CONFIGURATION --}}
                <div class="wizard-card">
                    <div class="wizard-card-title">Configuration</div>

                    {{-- GENERAL CONFIGURATION --}}
                    <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Working Hour (Default Shift)</label>
                            <select wire:model="default_shift_id" class="form-select">
                                <option value="">Select working hour</option>
                                @foreach ($shifts as $id => $shiftName)
                                    <option value="{{ $id }}">{{ $shiftName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Late Tolerance</label>
                            <input
                                type="number"
                                wire:model="default_late_tolerance"
                                placeholder="Set late tolerance (in minutes)"
                                class="form-input"
                                min="0"
                            />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Store/Location (Default)</label>
                            <select wire:model="default_work_location_id" class="form-select">
                                <option value="">Select store / location</option>
                                @foreach ($workLocations as $id => $locName)
                                    <option value="{{ $id }}">{{ $locName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="info-box-general">
                        All options will be apply to selected days (except <strong>custom option</strong> of day was set)
                    </div>

                    {{-- DAYS APPLIED LIST --}}
                    <div>
                        <div class="days-section-header">
                            <div class="form-label" style="font-size: 14px;">Days Applied</div>
                            <div class="days-quick-actions">
                                <button type="button" wire:click="selectAllDays" class="btn-pill-action btn-pill-green">
                                    Select All
                                </button>
                                <button type="button" wire:click="selectWorkDays" class="btn-pill-action btn-pill-blue">
                                    Work Days
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 12px;">
                            @foreach ($days as $dayKey => $day)
                                <div class="day-row-card {{ $day['is_active'] ? 'active-day' : '' }}">
                                    <div class="day-row-header">
                                        <div class="day-row-left">
                                            <label class="toggle-switch-wrapper">
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="days.{{ $dayKey }}.is_active"
                                                />
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="day-name-label {{ $day['is_active'] ? '' : 'inactive' }}">
                                                {{ $day['name'] }}
                                            </span>
                                        </div>

                                        @if ($day['is_active'])
                                            <div class="day-row-right">
                                                <span class="custom-option-label">Custom Option</span>
                                                <label class="toggle-switch-wrapper" style="width: 36px; height: 20px;">
                                                    <input
                                                        type="checkbox"
                                                        wire:click="toggleCustomOption('{{ $dayKey }}')"
                                                        {{ $day['has_custom_option'] ? 'checked' : '' }}
                                                    />
                                                    <span class="toggle-slider" style="border-radius: 20px;"></span>
                                                </label>
                                            </div>
                                        @else
                                            <span style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">
                                                Day Off (Libur)
                                            </span>
                                        @endif
                                    </div>

                                    {{-- EXPANDED CUSTOM OPTION FORM --}}
                                    @if ($day['is_active'] && ($day['has_custom_option'] ?? false))
                                        <div class="custom-option-body">
                                            <div class="form-group">
                                                <label class="form-label" style="font-size: 11px;">Working Hour (Custom)</label>
                                                <select wire:model="days.{{ $dayKey }}.shift_id" class="form-select" style="padding: 8px 10px; font-size: 12px;">
                                                    <option value="">Pilih shift khusus</option>
                                                    @foreach ($shifts as $id => $shiftName)
                                                        <option value="{{ $id }}">{{ $shiftName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label" style="font-size: 11px;">Late Tolerance (Mins)</label>
                                                <input
                                                    type="number"
                                                    wire:model="days.{{ $dayKey }}.late_tolerance"
                                                    placeholder="15"
                                                    class="form-input"
                                                    style="padding: 8px 10px; font-size: 12px;"
                                                />
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label" style="font-size: 11px;">Store / Location (Custom)</label>
                                                <select wire:model="days.{{ $dayKey }}.work_location_id" class="form-select" style="padding: 8px 10px; font-size: 12px;">
                                                    <option value="">Pilih lokasi khusus</option>
                                                    @foreach ($workLocations as $id => $locName)
                                                        <option value="{{ $id }}">{{ $locName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- FOOTER STEP 1 --}}
                    <div class="wizard-footer-actions">
                        <div></div>
                        <button type="button" wire:click="goToStep2" class="btn-wizard btn-wizard-primary">
                            <span>Lanjut ke Step 2: Implementing Working Group</span>
                            <x-filament::icon icon="heroicon-o-arrow-right" style="width: 18px; height: 18px;" />
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- STEP 2: IMPLEMENTING WORKING GROUP --}}
        @if ($currentStep === 2)
            <div class="wizard-card">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <div class="wizard-card-title">Employee Applied</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                            Working Group: <strong style="color: #0284c7;">{{ $name }}</strong> &bull; Mulai Berlaku: <strong>{{ \Carbon\Carbon::parse($data_applied_date)->translatedFormat('d F Y') }}</strong>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 10px; font-size: 13px; font-weight: 800; background: #0284c7; color: #ffffff; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);">
                            Selected ({{ number_format($totalSelected) }})
                        </span>
                        @if ($branch_id || $principal_id)
                            <button type="button" wire:click="addAllEmployeesFromArea" class="btn-wizard btn-wizard-secondary" style="padding: 6px 12px; font-size: 12px;">
                                <x-filament::icon icon="heroicon-o-user-plus" style="width: 16px; height: 16px;" />
                                <span>Tambah Semua di Area/Prinsiple Ini</span>
                            </button>
                        @endif
                        @if ($totalSelected > 0)
                            <button type="button" wire:click="removeAllEmployees" class="btn-wizard btn-wizard-secondary" style="padding: 6px 12px; font-size: 12px; color: #ef4444;">
                                <span>Kosongkan</span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- SELECT EMPLOYEE AUTOCOMPLETE --}}
                <div class="form-group" style="margin-top: 8px;">
                    <label class="form-label">
                        Select employee to be added
                    </label>
                    <select wire:model.live="employee_to_add" class="form-select" style="padding: 12px 14px;">
                        <option value="">-- Pilih karyawan untuk langsung ditambahkan ke list --</option>
                        @foreach ($availableEmployees as $empId => $empLabel)
                            <option value="{{ $empId }}">{{ $empLabel }}</option>
                        @endforeach
                    </select>
                    <div style="font-size: 11px; color: #64748b; font-style: italic;">
                        Selected employee will be automatically added to list
                    </div>
                </div>

                {{-- TABLE CONTROLS --}}
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <select wire:model.live="table_per_page" class="form-select" style="padding: 6px 12px; font-size: 12px; width: 70px;">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span style="font-size: 12px; color: #64748b;">entries per page</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 12px; font-weight: 700; color: #64748b;">Search:</span>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="table_search"
                            placeholder="Cari nama / NIK..."
                            class="form-input"
                            style="padding: 6px 12px; font-size: 12px; width: 220px;"
                        />
                    </div>
                </div>

                {{-- SELECTED EMPLOYEES DATA TABLE --}}
                <div class="step2-table-container">
                    <table class="step2-table">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Employee</th>
                                <th style="width: 35%;">Position/Area</th>
                                <th style="width: 15%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($selectedEmployees as $emp)
                                @php
                                    $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($emp->full_name) . '&background=0284c7&color=fff&size=64';
                                    if (!empty($emp->photo)) {
                                        try {
                                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($emp->photo)) {
                                                $photoUrl = asset('storage/' . $emp->photo);
                                            }
                                        } catch (\Throwable $e) {}
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <img
                                                src="{{ $photoUrl }}"
                                                alt="{{ $emp->full_name }}"
                                                style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1;"
                                            />
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a;">{{ $emp->full_name }}</div>
                                                <div style="font-size: 11px; color: #64748b; font-family: monospace;">
                                                    NIK: {{ $emp->employee_no ?? '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #0284c7;">{{ $emp->position_name ?? 'N/A' }}</div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            {{ $emp->branch_name ?? ($emp->principal_name ?? '-') }}
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <button
                                            type="button"
                                            wire:click="removeEmployee({{ $emp->id }})"
                                            style="padding: 6px 10px; border-radius: 8px; background: #fee2e2; color: #dc2626; border: none; cursor: pointer; transition: all 0.15s ease;"
                                            title="Hapus dari daftar"
                                        >
                                            <x-filament::icon icon="heroicon-o-trash" style="width: 16px; height: 16px;" />
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 36px; color: #94a3b8;">
                                        <x-filament::icon icon="heroicon-o-user-group" style="width: 36px; height: 36px; margin: 0 auto 8px auto; color: #cbd5e1;" />
                                        <div>No data available in table</div>
                                        <div style="font-size: 11px; margin-top: 4px;">Pilih karyawan dari dropdown di atas untuk menambahkan anggota Working Group.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                @if ($pagination['total_records'] > 0)
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: #64748b;">
                        <div>
                            Showing <strong>{{ $pagination['from'] }}</strong> to <strong>{{ $pagination['to'] }}</strong> of <strong>{{ $pagination['total_records'] }}</strong> records
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button
                                type="button"
                                wire:click="previousTablePage"
                                @if ($pagination['page'] <= 1) disabled @endif
                                style="padding: 4px 10px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; cursor: pointer; opacity: {{ $pagination['page'] <= 1 ? '0.4' : '1' }};"
                            >
                                &lsaquo;
                            </button>

                            <span>Halaman {{ $pagination['page'] }} / {{ $pagination['total_pages'] }}</span>

                            <button
                                type="button"
                                wire:click="nextTablePage({{ $pagination['total_pages'] }})"
                                @if ($pagination['page'] >= $pagination['total_pages']) disabled @endif
                                style="padding: 4px 10px; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; cursor: pointer; opacity: {{ $pagination['page'] >= $pagination['total_pages'] ? '0.4' : '1' }};"
                            >
                                &rsaquo;
                            </button>
                        </div>
                    </div>
                @endif

                {{-- FOOTER STEP 2 --}}
                <div class="wizard-footer-actions">
                    <button type="button" wire:click="goToStep1" class="btn-wizard btn-wizard-secondary">
                        <x-filament::icon icon="heroicon-o-arrow-left" style="width: 18px; height: 18px;" />
                        <span>Kembali ke Step 1</span>
                    </button>

                    <button type="button" wire:click="saveAndGenerateSchedule" class="btn-wizard btn-wizard-success" @if ($totalSelected === 0) disabled style="opacity: 0.5; cursor: not-allowed;" @endif>
                        <x-filament::icon icon="heroicon-o-check" style="width: 18px; height: 18px;" />
                        <span>Simpan & Generate Jadwal (Submit)</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
