<?php

namespace App\Filament\Resources\ReportTemplates\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ReportTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Dasar Template Form')
                    ->description('Tentukan nama form, prinsiple pemilik (bisa multipel), dan aturan umum pelaporan.')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('principals')
                                ->relationship('principals', 'name', modifyQueryUsing: fn ($query) => $query->where('principals.is_active', true)->with('company'))
                                ->getOptionLabelFromRecordUsing(fn (\App\Models\Principal $record) => $record->name . ($record->company ? " [{$record->company->name}]" : ''))
                                ->label('Prinsiple Klien (Pilihan Multipel)')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->helperText('Pilih satu atau lebih entitas prinsiple (misal: semua entitas PT WINGS SURYA / PT LION WINGS lintas entitas AMK/ATB/ATK)')
                                ->required(),
                            TextInput::make('title')
                                ->label('Judul Form Pelaporan')
                                ->placeholder('Contoh: Laporan Offtake & Stock Harian')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, callable $set, $get) {
                                    if ($operation === 'create' && empty($get('code')) && !empty($state)) {
                                        $set('code', 'RPT-' . strtoupper(Str::slug($state, '-')));
                                    }
                                })
                                ->required(),
                            TextInput::make('code')
                                ->label('Kode Form Template')
                                ->placeholder('RPT-OFFTAKE-DULUX')
                                ->required(),
                        ]),
                        Select::make('products')
                            ->relationship('products', 'name', modifyQueryUsing: function ($query, callable $get) {
                                $selectedPrincipals = $get('principals') ?? [];
                                if (!empty($selectedPrincipals)) {
                                    $query->whereIn('principal_id', $selectedPrincipals);
                                }
                                return $query->where('is_active', true)->orderBy('name');
                            })
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\Product $record) => "{$record->name} " . ($record->brand ? "[{$record->brand}]" : '') . " - " . $record->formatted_price)
                            ->label('Filter Parameter Produk Tertentu (Sesuai Prinsiple yang Dipilih)')
                            ->placeholder('Cari dan pilih produk spesifik (Opsional: Kosongkan jika berlaku untuk semua produk)')
                            ->helperText('Produk yang dipilih di sini akan menjadi daftar pilihan produk saat tim lapangan/SPG mengisi laporan ini.')
                            ->multiple()
                            ->searchable()
                            ->columnSpanFull(),
                        Grid::make(4)->schema([
                            Select::make('category')
                                ->label('Kategori Pelaporan')
                                ->options([
                                    'offtake' => 'Offtake / Penjualan Harian',
                                    'sellout' => 'Sell-Out (SPG / MD / Demo Event)',
                                    'stock' => 'Cek Stok & OOS (Barang Kosong)',
                                    'pricing' => 'Cek Harga & Price Tag Tracking',
                                    'price' => 'Price Monitoring (Harga & Kompetitor)',
                                    'promo' => 'Tracking Program Promo',
                                    'display' => 'Display & Sewa Display (Rent/Add Display)',
                                    'posm' => 'POSM & Material Promosi / Stiker',
                                    'competitor' => 'Market Share & Aktivitas Kompetitor',
                                    'expiry' => 'Monitoring Expired Date (Kadaluarsa)',
                                    'expired_date' => 'Monitoring Expired Date (Kadaluarsa)',
                                    'survey' => 'Survey Pasar / Profil Toko',
                                    'general' => 'Pelaporan Umum / Kunjungan Biasa',
                                ])
                                ->default('general')
                                ->searchable()
                                ->required(),
                            Toggle::make('require_gps')
                                ->label('Wajib Titik GPS')
                                ->default(true)
                                ->inline(false),
                            Toggle::make('require_signature')
                                ->label('Wajib Tanda Tangan')
                                ->default(false)
                                ->inline(false),
                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->default(true)
                                ->inline(false),
                        ]),
                        Textarea::make('description')
                            ->label('Deskripsi & Petunjuk Pengisian untuk Karyawan')
                            ->placeholder('Jelaskan instruksi pengisian form ini bagi SPG/MD di lapangan...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('🗓️ Pengaturan Frekuensi Jadwal & Target Pengisian Form')
                    ->description('Atur apakah form ini wajib diisi secara Harian (Daily), Mingguan (Weekly), atau Bulanan (Monthly) beserta target kuota dan hari aktifnya.')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('schedule_type')
                                ->label('Tipe Frekuensi Jadwal')
                                ->options([
                                    'daily' => '📅 Daily (Harian)',
                                    'weekly' => '🗓️ Weekly (Mingguan)',
                                    'monthly' => '📆 Monthly (Bulanan)',
                                ])
                                ->default('daily')
                                ->live()
                                ->required()
                                ->helperText(fn ($get) => match ($get('schedule_type')) {
                                    'weekly' => 'Laporan dikerjakan dengan kuota mingguan pada hari yang ditentukan.',
                                    'monthly' => 'Laporan dikerjakan dengan kuota bulanan (cut-off) pada hari yang ditentukan.',
                                    default => 'Laporan akan muncul dan dikerjakan setiap hari / hari kerja aktif.',
                                }),
                            TextInput::make('target_count')
                                ->label(fn ($get) => match ($get('schedule_type')) {
                                    'weekly' => '🎯 Target Pengisian (Per Minggu)',
                                    'monthly' => '🎯 Target Pengisian (Per Bulan / Cut-Off)',
                                    default => '🎯 Target Pengisian (Per Hari)',
                                })
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->visible(fn ($get) => in_array($get('schedule_type'), ['weekly', 'monthly']))
                                ->helperText(fn ($get) => match ($get('schedule_type')) {
                                    'weekly' => 'Contoh: Isi 2 jika wajib diisi 2x dalam 1 minggu.',
                                    'monthly' => 'Contoh: Isi 1 jika wajib diisi 1x dalam 1 periode cut-off bulanan.',
                                    default => 'Jumlah target pengisian.',
                                })
                                ->required(),
                            Select::make('report_days')
                                ->label(fn ($get) => match ($get('schedule_type')) {
                                    'daily' => '🗓️ Pilihan Hari Aktif (Opsional)',
                                    'weekly' => '🗓️ Pilihan Hari yang Ditentukan',
                                    'monthly' => '🗓️ Pilihan Hari yang Ditentukan',
                                    default => '🗓️ Hari Pelaporan',
                                })
                                ->placeholder('Pilih hari (Kosongkan jika berlaku setiap hari)')
                                ->options([
                                    'senin' => 'Senin (Monday)',
                                    'selasa' => 'Selasa (Tuesday)',
                                    'rabu' => 'Rabu (Wednesday)',
                                    'kamis' => 'Kamis (Thursday)',
                                    'jumat' => 'Jumat (Friday)',
                                    'sabtu' => 'Sabtu (Saturday)',
                                    'minggu' => 'Minggu (Sunday)',
                                ])
                                ->multiple()
                                ->searchable()
                                ->columnSpan(fn ($get) => in_array($get('schedule_type'), ['weekly', 'monthly']) ? 1 : 2)
                                ->helperText('Tentukan hari aktif laporan. Jika dikosongkan, form bebas diisi pada hari apa saja.'),
                        ]),
                    ]),

                Section::make('Visual Dynamic Form Builder (Google Form Style)')
                    ->description('Tambahkan daftar pertanyaan dan elemen input dinamis yang akan tampil di aplikasi mobile.')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('fields')
                            ->label('Daftar Pertanyaan / Form Fields')
                            ->relationship('fields')
                            ->orderColumn('order_index')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['field_label'] ?? null) 
                                ? "📋 {$state['field_label']} (" . strtoupper($state['field_type'] ?? 'TEXT') . ")" 
                                : '➕ Pertanyaan Baru')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field_label')
                                        ->label('Label Pertanyaan / Field')
                                        ->placeholder('Contoh: Nama Produk, Jumlah Terjual, Foto Display')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (empty($get('field_name')) && !empty($state)) {
                                                $set('field_name', Str::snake(Str::slug($state)));
                                            }
                                        })
                                        ->required(),
                                    TextInput::make('field_name')
                                        ->label('Key / Variable Name')
                                        ->placeholder('nama_produk, jumlah_terjual')
                                        ->helperText('Nama variabel unik di database (snake_case)')
                                        ->required(),
                                    Select::make('field_type')
                                        ->label('Tipe Input')
                                        ->options([
                                            'product_select' => '📦 Pilihan Produk Tertentu (Otomatis dari Template / Master SKU)',
                                            'text' => '📝 Teks Singkat',
                                            'textarea' => '📄 Paragraf / Catatan Panjang',
                                            'number' => '🔢 Angka / Kuantitas (Qty)',
                                            'currency' => '💰 Nilai Rupiah (IDR)',
                                            'dropdown' => '🔻 Dropdown Pilihan Tunggal',
                                            'radio' => '🔘 Radio Button (Pilihan Ganda)',
                                            'checkbox' => '☑️ Checkbox (Multi-Pilihan)',
                                            'camera_photo' => '📷 Foto Kamera Tunggal (Wajib Kamera)',
                                            'multi_photo' => '📸 Multi-Foto Kamera (Before/After/Cluster)',
                                            'signature' => '✍️ Tanda Tangan Digital (Signature Pad)',
                                            'barcode_scanner' => '🔍 Scan Barcode / QR Code',
                                            'month_year' => '🗓️ Pilih Bulan & Tahun (MM/YYYY - Expired Date)',
                                            'date' => '📅 Pilih Tanggal Lengkap (DD/MM/YYYY)',
                                            'time' => '⏰ Pilih Jam / Waktu',
                                            'rating_star' => '⭐ Rating Bintang (1-5)',
                                            'slider' => '🎚️ Skala Slider (0-100)',
                                            'gps_location' => '📍 Koordinat GPS Otomatis',
                                        ])
                                        ->live()
                                        ->required(),
                                ]),
                                Grid::make(4)->schema([
                                    TextInput::make('placeholder')
                                        ->label('Placeholder / Teks Petunjuk')
                                        ->placeholder('Masukkan data...'),
                                    TextInput::make('help_text')
                                        ->label('Keterangan Tambahan / Hint')
                                        ->placeholder('Contoh: Wajib foto tampak depan toko'),
                                    Toggle::make('is_required')
                                        ->label('Wajib Diisi (Mandatory)')
                                        ->default(false)
                                        ->inline(false),
                                    Toggle::make('is_readonly')
                                        ->label('Read Only (Hanya Baca)')
                                        ->helperText('Otomatis terkunci / tidak bisa diedit user')
                                        ->default(false)
                                        ->inline(false),
                                ]),
                                TagsInput::make('options')
                                    ->label('Opsi Pilihan Manual (Ketik dan Tekan Enter)')
                                    ->placeholder('Tambah opsi baru...')
                                    ->helperText('Untuk tipe Dropdown/Radio/Checkbox. Jika tipe Produk Tertentu dipilih, opsi otomatis diambil dari produk yang di-set pada form template.')
                                    ->visible(fn ($get) => in_array($get('field_type'), ['dropdown', 'radio', 'checkbox', 'product_select']))
                                    ->columnSpanFull(),
                                KeyValue::make('validation_rules')
                                    ->label('Aturan Validasi Tambahan (Opsional)')
                                    ->keyLabel('Kunci Aturan (min, max, regex, step)')
                                    ->valueLabel('Nilai Aturan')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('➕ Tambah Pertanyaan / Field Baru')
                            ->columnSpanFull(),
                    ]),

                Section::make('Penugasan Form Template (Form Assignment)')
                    ->description('Tentukan jabatan atau nama karyawan spesifik yang wajib mengisi form ini saat kunjungan lapangan (Mendukung Multi-Select).')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('positions')
                                ->relationship('positions', 'name')
                                ->label('🎯 Target Jabatan (Pilihan Multipel / Multi-Select)')
                                ->placeholder('Semua Jabatan (SPG, MD, TL, SPV, dll.)')
                                ->helperText('Pilih satu atau lebih jabatan yang wajib mengisi form ini. Kosongkan jika berlaku untuk semua jabatan.')
                                ->multiple()
                                ->searchable()
                                ->preload(),
                            Select::make('employees')
                                ->relationship('employees', 'full_name', modifyQueryUsing: function ($query, callable $get) {
                                    $selectedPrincipals = $get('principals') ?? [];
                                    if (!empty($selectedPrincipals)) {
                                        $query->whereIn('principal_id', $selectedPrincipals);
                                    }
                                    return $query->orderBy('full_name');
                                })
                                ->getOptionLabelFromRecordUsing(fn (\App\Models\Employee $record) => "{$record->full_name} ({$record->nik})" . ($record->position ? " - {$record->position->name}" : '') . ($record->principal ? " [{$record->principal->name}]" : ''))
                                ->label('👤 Target Nama Karyawan / Employee Spesifik (Multi-Select)')
                                ->placeholder('Cari dan pilih nama karyawan spesifik...')
                                ->helperText('Pilih nama-nama karyawan khusus yang ditugaskan form ini. Kosongkan jika berlaku umum sesuai prinsiple / jabatan.')
                                ->multiple()
                                ->searchable(),
                        ]),
                        Repeater::make('assignments')
                            ->label('Daftar Aturan Penugasan Spesifik Tambahan (Per Toko / Channel / Karyawan)')
                            ->relationship('assignments')
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('employee_id')
                                        ->relationship('employee', 'full_name')
                                        ->getOptionLabelFromRecordUsing(fn (\App\Models\Employee $record) => "{$record->full_name} ({$record->nik})")
                                        ->label('Nama Employee (Opsional)')
                                        ->placeholder('Cari Employee...')
                                        ->searchable(),
                                    Select::make('position_id')
                                        ->relationship('position', 'name')
                                        ->label('Jabatan Tertentu (Opsional)')
                                        ->placeholder('Semua Jabatan (SPG, MD, TL)')
                                        ->searchable()
                                        ->preload(),
                                    Select::make('work_location_id')
                                        ->relationship('workLocation', 'name')
                                        ->label('Toko / Outlet Spesifik (Opsional)')
                                        ->placeholder('Cari Toko / Outlet...')
                                        ->searchable(),
                                    Select::make('channel')
                                        ->label('Channel Penjualan (Opsional)')
                                        ->options([
                                            'Modern Trade' => 'Modern Trade (Supermarket/Hypermarket)',
                                            'General Trade' => 'General Trade (Toko Tradisional/Grosir)',
                                            'Minimarket' => 'Minimarket (Indomaret/Alfamart/SAT)',
                                            'Supermarket' => 'Supermarket (Grand Lucky/Super Indo)',
                                            'Hypermarket' => 'Hypermarket (Hypermart/Lotte/Transmart)',
                                            'Traditional / Ritel' => 'Traditional Market / Toko Cat Ritel',
                                        ])
                                        ->placeholder('Semua Channel'),
                                ]),
                            ])
                            ->addActionLabel('➕ Tambah Target Penugasan Khusus')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
