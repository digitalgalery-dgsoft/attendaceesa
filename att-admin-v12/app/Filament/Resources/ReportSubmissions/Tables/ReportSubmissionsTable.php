<?php

namespace App\Filament\Resources\ReportSubmissions\Tables;

use App\Models\Principal;
use App\Models\ReportTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->wrap(),

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
                        'pending', 'submitted' => 'Menunggu Verifikasi',
                        'approved', 'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'pending', 'submitted' => 'warning',
                        'approved', 'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
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

                Filter::make('submitted_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('submitted_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('submitted_at', '<=', $date));
                    }),
            ])
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
            ]);
    }
}
