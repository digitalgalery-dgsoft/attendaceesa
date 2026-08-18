<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitReportResource\Pages;
use App\Models\VisitReport;
use App\Models\ItineraryItem;
use App\Models\EmployeeSchedule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
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
    protected static string|\UnitEnum|null $navigationGroup = '4. Field Operations & Sales';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Visit Report Details')
                ->description('Informasi lengkap mengenai laporan kunjungan')
                ->columns(2)
                ->schema([
                        Select::make('itinerary_item_id')
                            ->label('Visit Kunjungan (Jadwal)')
                            ->nullable()
                            ->options(fn () => ItineraryItem::with(['itinerary.employee', 'workLocation'])
                                ->get()
                                ->mapWithKeys(fn ($item) => [
                                    $item->id => ($item->workLocation->name ?? 'Toko?') . ' - ' . ($item->itinerary->employee->full_name ?? 'Karyawan?'),
                                ])
                            )
                            ->searchable()
                            ->columnSpan(1),
                            
                        Select::make('status')
                            ->options([
                                'open_issue' => 'Open Issue',
                                'action_taken' => 'Action Taken',
                                'completed' => 'Completed',
                                'overdue' => 'Overdue',
                            ])
                            ->required()
                            ->label('Issue/Status')
                            ->columnSpan(1),
                            
                        DatePicker::make('deadline')
                            ->label('Deadline')
                            ->columnSpan(1),
                    ]),

                    Textarea::make('notes')
                        ->label('Catatan Kunjungan (Dari Mobile)')
                        ->maxLength(65535)
                        ->columnSpanFull(),

                    Section::make('Issue & Resolution')
                        ->columns(2)
                        ->schema([
                                Textarea::make('issue')
                                    ->label('Issue Description')
                                    ->maxLength(65535)
                                    ->rows(3),
                                Textarea::make('action_taken')
                                    ->label('Action Taken')
                                    ->maxLength(65535)
                                    ->rows(3),
                        ])
                        ->collapsible()
                        ->collapsed(false),
                    
                    Section::make('Target Report')
                        ->columns(2)
                        ->schema([
                            Select::make('target_type')
                                ->label('Tipe Target')
                                ->options([
                                    'Target Qty' => 'Target Qty',
                                    'Target Value' => 'Target Value',
                                    'Keduanya' => 'Keduanya',
                                ])
                                ->columnSpanFull(),
                            \Filament\Forms\Components\TextInput::make('target_qty')
                                ->label('Target (Qty)'),
                            \Filament\Forms\Components\TextInput::make('actual_qty')
                                ->label('Actual (Qty)'),
                            \Filament\Forms\Components\TextInput::make('target_value')
                                ->label('Target (Value)'),
                            \Filament\Forms\Components\TextInput::make('actual_value')
                                ->label('Actual (Value)'),
                        ])
                        ->collapsible()
                        ->collapsed(false),

                    Section::make('Evidence')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('photo_preview')
                                ->label('Preview Foto (Current)')
                                ->content(function ($record) {
                                    if ($record && $record->photo_path) {
                                        return new \Illuminate\Support\HtmlString('<img src="'.\Illuminate\Support\Facades\Storage::url($record->photo_path).'" alt="Photo" class="rounded-lg shadow-sm" style="max-height: 400px; width: auto;" />');
                                    }
                                    return '-';
                                })
                                ->hidden(fn ($record) => !$record || !$record->photo_path)
                                ->columnSpanFull(),

                            FileUpload::make('photo_path')
                                ->label('Upload / Ganti Foto')
                                ->disk('public')
                                ->directory('visit_reports')
                                ->image()
                                ->imageEditor()
                                ->downloadable()
                                ->openable()
                                ->columnSpanFull(),
                        ])
                        ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('store_location')
                    ->label('Store / Location')
                    ->getStateUsing(function (VisitReport $record): string {
                        // 1. Try from itinerary item (if set)
                        if ($record->itinerary_item_id && $record->itineraryItem?->workLocation) {
                            return $record->itineraryItem->workLocation->name;
                        }
                        // 2. Try from employee schedule on the report date
                        $scheduleDate = \Carbon\Carbon::parse($record->created_at)->toDateString();
                        $schedule = EmployeeSchedule::where('employee_id', $record->employee_id)
                            ->where('schedule_date', $scheduleDate)
                            ->with('workLocation')
                            ->first();
                        if ($schedule?->workLocation) {
                            return $schedule->workLocation->name;
                        }
                        return '-';
                    })
                    ->searchable(false)
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->color(fn (string $state): string => match ($state) {
                        'open_issue' => 'danger',
                        'action_taken' => 'info',
                        'completed' => 'success',
                        'overdue' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(fn (VisitReport $record) => $record->notes)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('deadline')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('photo_path')
                    ->label('Evidence'),
                TextColumn::make('created_at')
                    ->label('Report Date')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
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
