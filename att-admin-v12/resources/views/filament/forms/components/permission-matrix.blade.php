@php
    $categories = \App\Filament\Forms\Components\PermissionMatrix::getCategories();
    $allPermissionNames = [];
    foreach ($categories as $modules) {
        foreach ($modules as $module) {
            foreach ($module['actions'] as $act) {
                $allPermissionNames[] = $act['name'];
            }
        }
    }
    $allPermissionNames = array_unique($allPermissionNames);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <style>
        .perm-matrix-wrapper {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
        }
        .perm-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            gap: 12px;
        }
        .dark .perm-top-bar {
            background: #1e293b;
            border-color: #334155;
        }
        .perm-cat-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .dark .perm-cat-card {
            background: #0f172a;
            border-color: #334155;
        }
        .perm-cat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
        }
        .dark .perm-cat-header {
            background: #1e293b;
            border-bottom-color: #334155;
        }
        .perm-cat-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dark .perm-cat-title {
            color: #f8fafc;
        }
        .perm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .perm-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
        }
        .dark .perm-table th {
            background: #1e293b;
            color: #94a3b8;
            border-bottom-color: #334155;
        }
        .perm-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        .dark .perm-table td {
            border-bottom-color: #1e293b;
            color: #f1f5f9;
        }
        .perm-table tr:hover {
            background: #f8fafc;
        }
        .dark .perm-table tr:hover {
            background: #1e293b;
        }
        .perm-chk-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            user-select: none;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .perm-chk-label:hover {
            background: #f1f5f9;
        }
        .dark .perm-chk-label:hover {
            background: #334155;
        }
        .perm-btn-xs {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .perm-btn-xs:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .dark .perm-btn-xs {
            background: #1e293b;
            border-color: #475569;
            color: #cbd5e1;
        }
        .dark .perm-btn-xs:hover {
            background: #334155;
            color: #ffffff;
        }
        .perm-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            border: 1px solid #4f46e5;
            background: #4f46e5;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .perm-btn-primary:hover {
            background: #4338ca;
        }
        .perm-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .perm-btn-secondary:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .dark .perm-btn-secondary {
            background: #1e293b;
            border-color: #475569;
            color: #cbd5e1;
        }
    </style>

    <div
        x-data="{
            state: $wire.entangle('{{ $getStatePath() }}'),
            allPerms: {{ json_encode($allPermissionNames) }},
            init() {
                if (!Array.isArray(this.state)) {
                    this.state = [];
                }
            },
            has(perm) {
                return Array.isArray(this.state) && this.state.includes(perm);
            },
            toggle(perm) {
                if (!Array.isArray(this.state)) {
                    this.state = [];
                }
                const idx = this.state.indexOf(perm);
                if (idx > -1) {
                    this.state.splice(idx, 1);
                } else {
                    this.state.push(perm);
                }
            },
            toggleGroup(perms) {
                if (!Array.isArray(this.state)) {
                    this.state = [];
                }
                const allChecked = perms.every(p => this.state.includes(p));
                if (allChecked) {
                    this.state = this.state.filter(p => !perms.includes(p));
                } else {
                    perms.forEach(p => {
                        if (!this.state.includes(p)) {
                            this.state.push(p);
                        }
                    });
                }
            },
            isGroupChecked(perms) {
                return Array.isArray(this.state) && perms.length > 0 && perms.every(p => this.state.includes(p));
            },
            selectAll() {
                this.state = [...this.allPerms];
            },
            deselectAll() {
                this.state = [];
            }
        }"
        class="perm-matrix-wrapper"
    >
        {{-- TOP ACTION CONTROLS --}}
        <div class="perm-top-bar">
            <div style="font-size: 13px; font-weight: 700; color: #475569;">
                Pilih hak akses fitur yang dapat diakses oleh Role ini:
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="perm-btn-primary" @click="selectAll()">
                    ✓ Pilih Semua Hak Akses
                </button>
                <button type="button" class="perm-btn-secondary" @click="deselectAll()">
                    ✕ Kosongkan Semua
                </button>
            </div>
        </div>

        {{-- CATEGORIES SECTIONS --}}
        @foreach($categories as $categoryName => $modules)
            @php
                $categoryPerms = [];
                foreach ($modules as $mod) {
                    foreach ($mod['actions'] as $act) {
                        $categoryPerms[] = $act['name'];
                    }
                }
                $categoryPerms = array_unique($categoryPerms);
            @endphp

            <div class="perm-cat-card">
                <div class="perm-cat-header">
                    <div class="perm-cat-title">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 9999px; background: #4f46e5;"></span>
                        {{ $categoryName }}
                    </div>
                    <button
                        type="button"
                        class="perm-btn-xs"
                        @click="toggleGroup({{ json_encode($categoryPerms) }})"
                    >
                        <span x-text="isGroupChecked({{ json_encode($categoryPerms) }}) ? '✕ Batal Semua' : '✓ Pilih Semua Kategori'"></span>
                    </button>
                </div>

                <div style="overflow-x: auto;">
                    <table class="perm-table">
                        <thead>
                            <tr>
                                <th style="text-align: left; width: 32%;">Menu / Modul</th>
                                <th style="width: 14%;">View (Lihat)</th>
                                <th style="width: 14%;">Create (Tambah)</th>
                                <th style="width: 14%;">Update (Ubah)</th>
                                <th style="width: 14%;">Delete (Hapus)</th>
                                <th style="width: 12%; text-align: center;">Pilih Baris</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modules as $moduleKey => $module)
                                @php
                                    $rowPerms = array_column($module['actions'], 'name');
                                    $viewAct = $module['actions']['view'] ?? null;
                                    $createAct = $module['actions']['create'] ?? null;
                                    $updateAct = $module['actions']['update'] ?? null;
                                    $deleteAct = $module['actions']['delete'] ?? null;
                                @endphp
                                <tr>
                                    {{-- Module Label --}}
                                    <td style="font-weight: 700; color: #0f172a; text-align: left;">
                                        {{ $module['label'] }}
                                    </td>

                                    {{-- View Checkbox --}}
                                    <td style="text-align: center;">
                                        @if($viewAct)
                                            <label class="perm-chk-label" @click.prevent="toggle('{{ $viewAct['name'] }}')">
                                                <input
                                                    type="checkbox"
                                                    style="border-radius: 4px; border-color: #94a3b8; color: #4f46e5; width: 16px; height: 16px; cursor: pointer;"
                                                    :checked="has('{{ $viewAct['name'] }}')"
                                                />
                                                <span style="font-size: 11px; color: #64748b;">View</span>
                                            </label>
                                        @else
                                            <span style="color: #cbd5e1;">-</span>
                                        @endif
                                    </td>

                                    {{-- Create Checkbox --}}
                                    <td style="text-align: center;">
                                        @if($createAct)
                                            <label class="perm-chk-label" @click.prevent="toggle('{{ $createAct['name'] }}')">
                                                <input
                                                    type="checkbox"
                                                    style="border-radius: 4px; border-color: #94a3b8; color: #4f46e5; width: 16px; height: 16px; cursor: pointer;"
                                                    :checked="has('{{ $createAct['name'] }}')"
                                                />
                                                <span style="font-size: 11px; color: #64748b;">Create</span>
                                            </label>
                                        @else
                                            <span style="color: #cbd5e1;">-</span>
                                        @endif
                                    </td>

                                    {{-- Update Checkbox --}}
                                    <td style="text-align: center;">
                                        @if($updateAct)
                                            <label class="perm-chk-label" @click.prevent="toggle('{{ $updateAct['name'] }}')">
                                                <input
                                                    type="checkbox"
                                                    style="border-radius: 4px; border-color: #94a3b8; color: #4f46e5; width: 16px; height: 16px; cursor: pointer;"
                                                    :checked="has('{{ $updateAct['name'] }}')"
                                                />
                                                <span style="font-size: 11px; color: #64748b;">Update</span>
                                            </label>
                                        @else
                                            <span style="color: #cbd5e1;">-</span>
                                        @endif
                                    </td>

                                    {{-- Delete Checkbox --}}
                                    <td style="text-align: center;">
                                        @if($deleteAct)
                                            <label class="perm-chk-label" @click.prevent="toggle('{{ $deleteAct['name'] }}')">
                                                <input
                                                    type="checkbox"
                                                    style="border-radius: 4px; border-color: #94a3b8; color: #4f46e5; width: 16px; height: 16px; cursor: pointer;"
                                                    :checked="has('{{ $deleteAct['name'] }}')"
                                                />
                                                <span style="font-size: 11px; color: #64748b;">Delete</span>
                                            </label>
                                        @else
                                            <span style="color: #cbd5e1;">-</span>
                                        @endif
                                    </td>

                                    {{-- Row Quick Toggle --}}
                                    <td style="text-align: center;">
                                        <button
                                            type="button"
                                            class="perm-btn-xs"
                                            @click="toggleGroup({{ json_encode($rowPerms) }})"
                                        >
                                            <span x-text="isGroupChecked({{ json_encode($rowPerms) }}) ? '✕ Batal' : '✓ Semua'"></span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-dynamic-component>
