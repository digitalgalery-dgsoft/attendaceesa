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
                                ->relationship('principals', 'name', modifyQueryUsing: fn ($query) => $query->with('company'))
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
                            ->placeholder('Pilih satu atau beberapa produk spesifik (Opsional: Kosongkan jika berlaku untuk semua produk prinsiple)')
                            ->helperText('Produk yang dipilih di sini akan menjadi daftar pilihan produk saat tim lapangan/SPG mengisi laporan ini.')
                            ->multiple()
                            ->searchable()
                            ->preload()
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
                        Select::make('report_days')
                            ->label('🗓️ Hari Pelaporan Wajib / Jadwal Pengisian (Opsional)')
                            ->placeholder('Pilih satu atau lebih hari (Kosongkan jika berlaku setiap hari)')
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
                            ->helperText('Tentukan hari form laporan ini harus dilakukan (misal: Senin - Jumat untuk weekday, atau Sabtu - Minggu untuk akhir pekan). Jika dikosongkan, form berlaku setiap hari.')
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Deskripsi & Petunjuk Pengisian untuk Karyawan')
                            ->placeholder('Jelaskan instruksi pengisian form ini bagi SPG/MD di lapangan...')
                            ->rows(2)
                            ->columnSpanFull(),
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
                                ->placeholder('Pilih satu atau lebih nama karyawan spesifik...')
                                ->helperText('Pilih nama-nama karyawan khusus yang ditugaskan form ini. Kosongkan jika berlaku umum sesuai prinsiple / jabatan.')
                                ->multiple()
                                ->searchable()
                                ->preload(),
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
                                        ->placeholder('Semua Employee')
                                        ->searchable()
                                        ->preload(),
                                    Select::make('position_id')
                                        ->relationship('position', 'name')
                                        ->label('Jabatan Tertentu (Opsional)')
                                        ->placeholder('Semua Jabatan (SPG, MD, TL)')
                                        ->searchable()
                                        ->preload(),
                                    Select::make('work_location_id')
                                        ->relationship('workLocation', 'name')
                                        ->label('Toko / Outlet Spesifik (Opsional)')
                                        ->placeholder('Semua Toko / Outlet')
                                        ->searchable()
                                        ->preload(),
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
