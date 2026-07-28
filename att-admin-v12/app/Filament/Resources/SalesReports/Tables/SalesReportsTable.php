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
                TextColumn::make('client_name')
                    ->searchable(),
                TextColumn::make('client_company')
                    ->searchable(),
                TextColumn::make('revenue')
                    ->numeric()
                    ->sortable()
                    ->money('IDR'),
                TextColumn::make('report_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Deal' => 'success',
                        'Follow Up' => 'warning',
                        'Lost' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('receipt_image')
                    ->label('Evidence'),
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
