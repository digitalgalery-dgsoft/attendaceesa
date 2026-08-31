<?php

namespace App\Filament\Resources\ReportTemplates\Tables;

use App\Models\ReportTemplate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReportTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Form Template')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (ReportTemplate $record) => $record->code),
                TextColumn::make('principals_list')
                    ->label('Prinsiple Klien')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(fn (ReportTemplate $record) => $record->principals->pluck('name')->unique()->values()->toArray())
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('principals', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'offtake', 'sellout' => 'success',
                        'stock' => 'warning',
                        'pricing', 'price' => 'info',
                        'promo' => 'primary',
                        'display', 'posm' => 'danger',
                        'competitor' => 'gray',
                        'expiry', 'expired_date' => 'danger',
                        'survey' => 'warning',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'offtake' => 'Offtake / Sales',
                        'sellout' => 'Sell-Out',
                        'stock' => 'Cek Stock / OOS',
                        'pricing' => 'Price & Tag',
                        'price' => 'Price Check',
                        'promo' => 'Promo Program',
                        'display' => 'Display Tracker',
                        'posm' => 'POSM & Sticker',
                        'competitor' => 'Competitor Share',
                        'expiry', 'expired_date' => 'Expired Alert',
                        'survey' => 'Survey',
                        'general' => 'General',
                        default => ucfirst($state ?? '-'),
                    })
                    ->sortable(),
                TextColumn::make('fields_count')
                    ->label('Total Field')
                    ->counts('fields')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
                TextColumn::make('submissions_count')
                    ->label('Laporan Masuk')
                    ->counts('submissions')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                IconColumn::make('require_gps')
                    ->label('GPS')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('require_signature')
                    ->label('Sign')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('principals')
                    ->relationship('principals', 'name', fn ($query) => $query->where('principals.is_active', true))
                    ->label('Filter Prinsiple')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category')
                    ->label('Filter Kategori')
                    ->options([
                        'offtake' => 'Offtake / Sales',
                        'sellout' => 'Sell-Out',
                        'stock' => 'Cek Stock / OOS',
                        'pricing' => 'Price & Tag',
                        'price' => 'Price Check',
                        'promo' => 'Promo Tracking',
                        'display' => 'Display Tracker',
                        'posm' => 'POSM & Sticker',
                        'competitor' => 'Competitor Share',
                        'expiry' => 'Expired Alert (Expiry)',
                        'expired_date' => 'Expired Alert (Expired Date)',
                        'survey' => 'Survey',
                        'general' => 'General',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                Action::make('clone')
                    ->label('Duplikasi')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Duplikasi Template Form')
                    ->modalDescription('Apakah Anda ingin menduplikasi template form ini beserta seluruh daftar pertanyaan field di dalamnya?')
                    ->action(function (ReportTemplate $record) {
                        $newTemplate = $record->replicate([
                            'code',
                        ]);
                        $newTemplate->title = "{$record->title} (Salinan)";
                        $newTemplate->code = "{$record->code}-COPY-" . rand(100, 999);
                        $newTemplate->save();

                        // Sync principals
                        $newTemplate->principals()->sync($record->principals->pluck('id'));

                        // Copy fields
                        foreach ($record->fields as $field) {
                            $newField = $field->replicate(['report_template_id']);
                            $newField->report_template_id = $newTemplate->id;
                            $newField->save();
                        }

                        // Copy assignments
                        foreach ($record->assignments as $assignment) {
                            $newAssignment = $assignment->replicate(['report_template_id']);
                            $newAssignment->report_template_id = $newTemplate->id;
                            $newAssignment->save();
                        }

                        Notification::make()
                            ->title('Template Berhasil Diduplikasi')
                            ->body("Template '{$newTemplate->title}' berhasil dibuat.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
