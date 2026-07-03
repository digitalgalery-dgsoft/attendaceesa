<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitReportResource\Pages;
use App\Models\VisitReport;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportAction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class VisitReportResource extends Resource
{
    protected static ?string $model = VisitReport::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('itinerary_item_id')
                ->relationship('itineraryItem', 'id') // Ideally this should show store name and employee name
                ->label('Visit Kunjungan')
                ->required()
                ->searchable(),
            Select::make('issue_type')
                ->options([
                    'open_issue' => 'Open Issue',
                    'action_taken' => 'Action Taken',
                    'completed' => 'Completed',
                    'overdue' => 'Overdue',
                ])
                ->required()
                ->label('Issue/Status'),
            Textarea::make('action')
                ->label('Action Taken')
                ->maxLength(65535)
                ->columnSpanFull(),
            Textarea::make('target')
                ->label('Target')
                ->maxLength(65535)
                ->columnSpanFull(),
            Textarea::make('actual')
                ->label('Actual Result')
                ->maxLength(65535)
                ->columnSpanFull(),
            DatePicker::make('deadline')
                ->label('Deadline'),
            Textarea::make('notes')
                ->label('Notes')
                ->maxLength(65535)
                ->columnSpanFull(),
            FileUpload::make('attachment_path')
                ->label('Attachment / Evidence')
                ->directory('visit-reports')
                ->image()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('itineraryItem.itinerary.employee.first_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('itineraryItem.workLocation.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issue_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->color(fn (string $state): string => match ($state) {
                        'open_issue' => 'danger',
                        'action_taken' => 'info',
                        'completed' => 'success',
                        'overdue' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('deadline')
                    ->date()
                    ->sortable(),
                ImageColumn::make('attachment_path')
                    ->label('Evidence'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\VisitReportExporter::class),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitReports::route('/'),
            'create' => Pages\CreateVisitReport::route('/create'),
            'edit' => Pages\EditVisitReport::route('/{record}/edit'),
        ];
    }
}
