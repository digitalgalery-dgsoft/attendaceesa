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
                    ->description('Tentukan nama form, prinsiple pemilik, dan aturan umum pelaporan.')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('principal_id')
                                ->relationship('principal', 'name')
                                ->label('Prinsiple Klien')
                                ->searchable()
                                ->preload()
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
                        Grid::make(4)->schema([
                            Select::make('category')
                                ->label('Kategori Pelaporan')
                                ->options([
                                    'offtake' => 'Offtake / Sell-Out (Penjualan)',
                                    'stock' => 'Cek Stok & OOS (Barang Kosong)',
                                    'pricing' => 'Harga & Promo Tracking',
                                    'display' => 'Display & Sewa Display (Rent/Add Display)',
                                    'posm' => 'POSM & Sticker Tracker',
                                    'competitor' => 'Market Share & Kompetitor Tracking',
                                    'expired_date' => 'Monitoring Expired Date (Kadaluarsa)',
                                    'survey' => 'Survey Pasar / Konsumen',
                                    'general' => 'Pelaporan Umum / Kunjungan Biasa',
                                ])
                                ->default('general')
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
                                            'date' => '📅 Pilih Tanggal',
                                            'time' => '⏰ Pilih Jam / Waktu',
                                            'rating_star' => '⭐ Rating Bintang (1-5)',
                                            'slider' => '🎚️ Skala Slider (0-100)',
                                            'gps_location' => '📍 Koordinat GPS Otomatis',
                                        ])
                                        ->live()
                                        ->required(),
                                ]),
                                Grid::make(3)->schema([
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
                                ]),
                                TagsInput::make('options')
                                    ->label('Opsi Pilihan (Ketik dan Tekan Enter untuk Setiap Opsi)')
                                    ->placeholder('Tambah opsi baru...')
                                    ->helperText('Hanya berlaku untuk tipe Dropdown, Radio Button, Checkbox, atau Daftar SKU Produk')
                                    ->visible(fn ($get) => in_array($get('field_type'), ['dropdown', 'radio', 'checkbox']))
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
                    ->description('Tentukan karyawan mana yang wajib mengisi form ini saat kunjungan lapangan.')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('assignments')
                            ->label('Daftar Aturan Penugasan Form')
                            ->relationship('assignments')
                            ->schema([
                                Grid::make(3)->schema([
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
                            ->addActionLabel('➕ Tambah Target Penugasan')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
