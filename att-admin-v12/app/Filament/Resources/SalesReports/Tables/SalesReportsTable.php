<?php

namespace App\Filament\Resources\SalesReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use App\Models\SalesReport;
use App\Services\AIService;

class SalesReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('store_name')
                    ->label('Nama Toko/Outlet')
                    ->searchable(),
                TextColumn::make('oos_status')
                    ->label('OOS')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aman' => 'success',
                        'Kosong' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('plano_status')
                    ->label('Planogram')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sesuai' => 'success',
                        'Tidak Sesuai' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('promo_status')
                    ->label('Promo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Berjalan' => 'success',
                        'Tidak Berjalan' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('report_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('photo_oos')
                    ->label('Foto OOS')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('photo_plano')
                    ->label('Foto Plano')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('photo_promo')
                    ->label('Foto Promo')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('ai_insight')
                    ->label('AI Insights')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->form([
                        Textarea::make('analysis')
                            ->label('Hasil Analisa & Saran AI')
                            ->disabled()
                            ->rows(12)
                    ])
                    ->fillForm(function (SalesReport $record): array {
                        return [
                            'analysis' => AIService::generateSalesAnalysis($record),
                        ];
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
