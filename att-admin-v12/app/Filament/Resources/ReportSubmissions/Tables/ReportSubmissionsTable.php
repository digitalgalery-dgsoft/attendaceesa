<?php

namespace App\Filament\Resources\ReportSubmissions\Tables;

use App\Models\Principal;
use App\Models\ReportTemplate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submission_code')
                    ->label('Kode Laporan')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('submitted_at')
                    ->label('Waktu Submit')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('template.title')
                    ->label('Form Pelaporan')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(function ($record) {
                        $tipeOos = null;
                        $oosItems = null;
                        $offtakeItems = null;

                        if ($record->relationLoaded('values') || $record->values()->exists()) {
                            foreach ($record->values as $v) {
                                $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
                                if ($fn === 'tipe_laporan_oos') {
                                    $tipeOos = strtolower(trim((string)($v->value_text ?? $v->value_json)));
                                } elseif ($fn === 'oos_items_json' && !empty($v->value_json)) {
                                    $oosItems = is_array($v->value_json) ? $v->value_json : json_decode((string)$v->value_json, true);
                                } elseif ($fn === 'offtake_items_json' && !empty($v->value_json)) {
                                    $offtakeItems = is_array($v->value_json) ? $v->value_json : json_decode((string)$v->value_json, true);
                                }
                            }
                        }

                        if ($tipeOos === 'no_oos') {
                            return '✅ Stok Lengkap (No OOS)';
                        }

                        if (is_array($oosItems) && count($oosItems) > 0) {
                            $maxLama = max(array_map(fn($it) => max(1, (int)($it['lama_oos_hari'] ?? 1)), $oosItems) ?: [1]);
                            $names = array_map(fn($it) => $it['product_name'] ?? ($it['nama_produk'] ?? 'SKU'), $oosItems);
                            $preview = !empty($names) ? ' - ' . implode(', ', array_slice($names, 0, 2)) : '';
                            return '⚠️ ' . count($oosItems) . ' SKU Kosong' . " ({$maxLama} Hari)" . $preview;
                        }

                        if (is_array($offtakeItems) && count($offtakeItems) > 0) {
                            return '📦 ' . count($offtakeItems) . ' SKU Terjual';
                        }

                        return null;
                    }),

                TextColumn::make('employee.full_name')
                    ->label('Promotor / SPG')
                    ->description(fn ($record) => $record->employee?->nik ?? '')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('store_name')
                    ->label('Nama Toko / Outlet')
                    ->formatStateUsing(fn ($record) => $record->store_name ?? $record->workLocation?->name ?? $record->itineraryItem?->destination ?? 'Kunjungan Toko')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('principal.name')
                    ->label('Prinsiple')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'rejected' => 'Ditolak',
                        default => 'Terkirim',
                    })
                    ->color(fn ($state) => match($state) {
                        'rejected' => 'danger',
                        default => 'success',
                    }),

                IconColumn::make('is_within_radius')
                    ->label('Radius')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->is_within_radius ? 'Dalam radius toko' : 'Di luar radius toko'),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                SelectFilter::make('quick_period')
                    ->label('Periode Cepat')
                    ->options([
                        'today' => 'Hari Ini',
                        'yesterday' => 'Kemarin',
                        'this_week' => 'Minggu Ini',
                        'this_month' => 'Bulan Ini',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereDate('submitted_at', today()),
                            'yesterday' => $query->whereDate('submitted_at', today()->subDay()),
                            'this_week' => $query->whereBetween('submitted_at', [now()->startOfWeek(), now()->endOfWeek()]),
                            'this_month' => $query->whereMonth('submitted_at', now()->month)->whereYear('submitted_at', now()->year),
                            default => $query,
                        };
                    }),

                Filter::make('submitted_date')
                    ->label('Filter Tanggal Laporan')
                    ->form([
                        DatePicker::make('date')
                            ->label('Pilih Tanggal Spesifik')
                            ->placeholder('Pilih tanggal laporan'),
                        DatePicker::make('from')
                            ->label('Dari Tanggal (Rentang)')
                            ->placeholder('Mulai dari tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal (Rentang)')
                            ->placeholder('Sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date'] ?? null, fn ($q, $date) => $q->whereDate('submitted_at', $date))
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('submitted_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('submitted_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['date'])) {
                            $indicators['date'] = 'Tanggal: ' . \Carbon\Carbon::parse($data['date'])->translatedFormat('d M Y');
                        }
                        if (!empty($data['from'])) {
                            $indicators['from'] = 'Dari: ' . \Carbon\Carbon::parse($data['from'])->translatedFormat('d M Y');
                        }
                        if (!empty($data['until'])) {
                            $indicators['until'] = 'Sampai: ' . \Carbon\Carbon::parse($data['until'])->translatedFormat('d M Y');
                        }
                        return $indicators;
                    }),

                SelectFilter::make('report_template_id')
                    ->label('Filter Form Template')
                    ->options(fn () => ReportTemplate::where('is_active', true)->pluck('title', 'id'))
                    ->searchable(),

                SelectFilter::make('principal_id')
                    ->label('Filter Prinsiple')
                    ->options(fn () => Principal::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status Verifikasi')
                    ->options([
                        'submitted' => 'Menunggu Verifikasi',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make()->label('Detail Isian'),
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Laporan Masuk')
                    ->modalDescription('Laporan ini akan ditandai sebagai valid dan terverifikasi.')
                    ->visible(fn ($record) => $record->status === 'submitted')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'verified',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Laporan Berhasil Diverifikasi')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('verification_notes')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->visible(fn ($record) => $record->status === 'submitted')
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                            'verification_notes' => $data['verification_notes'],
                        ]);
                        Notification::make()
                            ->title('Laporan Ditolak')
                            ->warning()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Laporan Terpilih')
                        ->modalHeading('Hapus Data Laporan Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus seluruh data laporan masuk yang dipilih? Data rincian isian formulir dan berkas foto terkait akan dihapus secara permanen.')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
