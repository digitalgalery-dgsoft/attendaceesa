<x-filament-panels::page>
    <style>
        .vcal-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .vcal-filter-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .vcal-filter-card {
            background: #1e293b;
            border-color: #334155;
        }

        .vcal-nav-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .vcal-nav-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .vcal-month-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .dark .vcal-month-title {
            color: #f8fafc;
        }

        .vcal-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
        }
        .vcal-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        .dark .vcal-btn {
            background: #334155;
            border-color: #475569;
            color: #f1f5f9;
        }
        .dark .vcal-btn:hover {
            background: #475569;
        }

        .vcal-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 1px;
            background: #cbd5e1;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .dark .vcal-grid {
            background: #334155;
            border-color: #334155;
        }

        .vcal-day-header {
            background: #f8fafc;
            padding: 10px 8px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dark .vcal-day-header {
            background: #0f172a;
            color: #94a3b8;
        }
        .vcal-day-header.weekend {
            color: #dc2626;
            background: #fef2f2;
        }
        .dark .vcal-day-header.weekend {
            color: #f87171;
            background: #451a1a;
        }

        .vcal-cell {
            background: #ffffff;
            min-height: 120px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: background 0.15s ease;
            position: relative;
        }
        .dark .vcal-cell {
            background: #1e293b;
        }
        .vcal-cell.other-month {
            background: #f8fafc;
            opacity: 0.45;
        }
        .dark .vcal-cell.other-month {
            background: #0f172a;
            opacity: 0.35;
        }
        .vcal-cell.today {
            background: #eff6ff;
            border: 2px solid #3b82f6 !important;
        }
        .dark .vcal-cell.today {
            background: #1e3a8a33;
            border: 2px solid #60a5fa !important;
        }

        .vcal-cell-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .vcal-date-number {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .dark .vcal-date-number {
            color: #f1f5f9;
        }
        .vcal-cell.today .vcal-date-number {
            background: #2563eb;
            color: #ffffff;
        }

        .vcal-add-btn {
            opacity: 0;
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #0f172a;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
        }
        .vcal-cell:hover .vcal-add-btn {
            opacity: 1;
        }
        .vcal-add-btn:hover {
            background: #2563eb;
            color: #ffffff;
        }
        .dark .vcal-add-btn {
            background: #334155;
            color: #f8fafc;
        }
        .dark .vcal-add-btn:hover {
            background: #3b82f6;
        }

        .vcal-cards-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            overflow-y: auto;
            max-height: 140px;
        }

        .vcal-item-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 3px solid #16a34a;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 11px;
            line-height: 1.3;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .vcal-item-card:hover {
            background: #dcfce7;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        .dark .vcal-item-card {
            background: #064e3b33;
            border-color: #065f46;
            border-left-color: #10b981;
        }
        .dark .vcal-item-card:hover {
            background: #064e3b66;
        }

        .vcal-item-card.draft {
            background: #fefce8;
            border-color: #fef08a;
            border-left-color: #ca8a04;
        }
        .dark .vcal-item-card.draft {
            background: #713f1233;
            border-color: #854d0e;
            border-left-color: #eab308;
        }

        .vcal-item-card.cancelled {
            background: #fef2f2;
            border-color: #fecaca;
            border-left-color: #dc2626;
            opacity: 0.75;
        }
        .dark .vcal-item-card.cancelled {
            background: #7f1d1d33;
            border-color: #991b1b;
            border-left-color: #ef4444;
        }

        .vcal-item-title {
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .vcal-item-title {
            color: #f8fafc;
        }

        .vcal-item-meta {
            font-size: 10px;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
        }
        .dark .vcal-item-meta {
            color: #94a3b8;
        }

        .vcal-badge-checkin {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            background: #2563eb;
            color: #ffffff;
            font-size: 9px;
            font-weight: 600;
            padding: 1px 4px;
            border-radius: 4px;
        }

        /* Modal Styles */
        .vcal-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .vcal-modal-box {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 680px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            display: flex;
            flex-direction: column;
        }
        .dark .vcal-modal-box {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }

        .vcal-modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .vcal-modal-header {
            border-bottom-color: #334155;
        }

        .vcal-modal-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .vcal-modal-footer {
            padding: 14px 20px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }
        .dark .vcal-modal-footer {
            background: #0f172a;
            border-top-color: #334155;
        }

        .vcal-detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .vcal-detail-table th {
            background: #f1f5f9;
            color: #334155;
            text-align: left;
            padding: 8px 10px;
            font-weight: 700;
            border-bottom: 1px solid #cbd5e1;
        }
        .dark .vcal-detail-table th {
            background: #0f172a;
            color: #94a3b8;
            border-bottom-color: #334155;
        }
        .vcal-detail-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .dark .vcal-detail-table td {
            border-bottom-color: #334155;
            color: #f1f5f9;
        }
    </style>

    <div class="vcal-container">
        <!-- Filter Card & Month Navigation -->
        <div class="vcal-filter-card">
            <div class="vcal-nav-bar">
                <div class="vcal-nav-controls">
                    <button type="button" wire:click="prevMonth" class="vcal-btn">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Bulan Lalu
                    </button>
                    <button type="button" wire:click="today" class="vcal-btn">Hari Ini</button>
                    <button type="button" wire:click="nextMonth" class="vcal-btn">
                        Bulan Depan
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <div class="vcal-month-title">
                    {{ Carbon\Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y') }}
                </div>

                <div style="font-size: 13px; color: #64748b;" class="dark:text-gray-400 font-medium">
                    Total: <strong class="text-primary-600 dark:text-primary-400">{{ $this->totalSchedulesInMonth }}</strong> Visit Terjadwal
                </div>
            </div>

            <!-- Filters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Pilih Bulan & Tahun</label>
                    <div class="flex gap-1">
                        <select wire:model.live="month" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 px-2">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                        <select wire:model.live="year" class="w-24 text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 px-2">
                            @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Region / Area</label>
                    <select wire:model.live="branch_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 px-2">
                        <option value="">Semua Area</option>
                        @foreach ($this->branchOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Prinsiple</label>
                    <select wire:model.live="principal_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 px-2">
                        <option value="">Semua Prinsiple</option>
                        @foreach ($this->principalOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Karyawan</label>
                    <select wire:model.live="employee_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 px-2">
                        <option value="">Semua Karyawan</option>
                        @foreach ($this->employeeOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Pencarian</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama karyawan / toko..." class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 px-2">
                </div>
            </div>
        </div>

        <!-- Monthly Calendar Grid -->
        <div class="vcal-grid">
            <!-- Day of Week Headers (Senin - Minggu) -->
            <div class="vcal-day-header">Senin</div>
            <div class="vcal-day-header">Selasa</div>
            <div class="vcal-day-header">Rabu</div>
            <div class="vcal-day-header">Kamis</div>
            <div class="vcal-day-header">Jumat</div>
            <div class="vcal-day-header weekend">Sabtu</div>
            <div class="vcal-day-header weekend">Minggu</div>

            <!-- Day Cells -->
            @foreach ($this->calendarDays as $day)
                <div class="vcal-cell {{ $day['is_current_month'] ? '' : 'other-month' }} {{ $day['is_today'] ? 'today' : '' }}">
                    <div class="vcal-cell-header">
                        <span class="vcal-date-number">{{ $day['day_number'] }}</span>
                        @if ($day['is_current_month'])
                            <button type="button" wire:click="openAddModal('{{ $day['date_string'] }}')" class="vcal-add-btn" title="Tambah Jadwal Visit pada tanggal {{ $day['date_string'] }}">+</button>
                        @endif
                    </div>

                    <!-- List of Visit Schedule Cards -->
                    <div class="vcal-cards-list">
                        @foreach ($day['schedules'] as $sch)
                            <div wire:click="openDetailModal({{ $sch['id'] }})" class="vcal-item-card {{ $sch['status'] }}" title="Klik untuk melihat detail kunjungan">
                                <div class="vcal-item-title">
                                    {{ $sch['employee_name'] }} <span class="font-normal text-gray-500 dark:text-gray-400">({{ $sch['position'] }} - {{ $sch['area'] }})</span>
                                </div>
                                <div class="vcal-item-meta">
                                    <span>📍 {{ $sch['location_count'] }} Lokasi</span>
                                    @if ($sch['has_checkin'])
                                        <span class="vcal-badge-checkin" title="Lokasi ini dijadikan titik check-in absensi">✓ Check-in</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal Detail Schedule Visit -->
    @if ($showDetailModal && $selectedItinerary)
        <div class="vcal-modal-overlay" wire:click.self="closeDetailModal">
            <div class="vcal-modal-box animate-scale-up">
                <div class="vcal-modal-header">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Detail Jadwal Visit (Visit Schedule)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ Carbon\Carbon::parse($selectedItinerary['date'])->translatedFormat('l, d F Y') }}</p>
                    </div>
                    <button type="button" wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="vcal-modal-body">
                    <!-- Employee Profile Summary -->
                    <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Nama Karyawan:</span>
                            <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $selectedItinerary['employee_name'] }}</div>
                            <div class="text-gray-600 dark:text-gray-300">NIK: {{ $selectedItinerary['employee_no'] }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Jabatan & Area:</span>
                            <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $selectedItinerary['position'] }} - {{ $selectedItinerary['area'] }}</div>
                            <div class="text-gray-600 dark:text-gray-300">Prinsiple: {{ $selectedItinerary['principal'] }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Status Visit:</span>
                            <div>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold uppercase {{ $selectedItinerary['status'] === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : ($selectedItinerary['status'] === 'draft' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $selectedItinerary['status'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if (!empty($selectedItinerary['notes']))
                        <div class="text-xs text-gray-600 dark:text-gray-400 p-2.5 bg-blue-50/50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                            <strong>Catatan:</strong> {{ $selectedItinerary['notes'] }}
                        </div>
                    @endif

                    <!-- List of Visit Locations -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">Daftar Titik / Toko Kunjungan</h4>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="vcal-detail-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; text-align: center;">#</th>
                                        <th>Lokasi / Toko</th>
                                        <th>Prinsiple</th>
                                        <th>Tipe Kunjungan</th>
                                        <th style="text-align: center;">Lokasi Check-in</th>
                                        <th>Agenda / Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($selectedItinerary['items'] as $item)
                                        <tr>
                                            <td style="text-align: center; font-weight: bold;">{{ $item['sequence'] }}</td>
                                            <td>
                                                <div class="font-bold text-gray-900 dark:text-white">{{ $item['location_name'] }}</div>
                                                <div class="text-[11px] text-gray-500">{{ $item['location_address'] }}</div>
                                            </td>
                                            <td>{{ $item['principal_name'] ?: '-' }}</td>
                                            <td>
                                                <span class="inline-block px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-[11px] capitalize">
                                                    {{ $item['visit_type'] }}
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                @if ($item['is_checkin_location'])
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                        ✓ Ya (Check-in)
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="text-gray-600 dark:text-gray-300">{{ $item['notes'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-gray-500">Belum ada lokasi kunjungan yang ditambahkan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="vcal-modal-footer">
                    <button type="button" wire:click="deleteItinerary({{ $selectedItinerary['id'] }})" wire:confirm="Apakah Anda yakin ingin menghapus jadwal visit ini?" class="vcal-btn" style="color: #dc2626; border-color: #fca5a5;">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Hapus Jadwal
                    </button>
                    <a href="{{ App\Filament\Resources\Itineraries\ItineraryResource::getUrl('edit', ['record' => $selectedItinerary['id']]) }}" class="vcal-btn" style="background: #2563eb; color: #ffffff; border-color: #2563eb;">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Jadwal Visit
                    </a>
                    <button type="button" wire:click="closeDetailModal" class="vcal-btn">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
