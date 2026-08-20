<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Attendance & Time Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Leave Requests';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->relationship('employee', 'full_name')
                ->searchable()
                ->required(),
            Select::make('type')
                ->options([
                    'sakit' => 'Sakit',
                    'izin' => 'Izin',
                    'cuti' => 'Cuti',
                    'off' => 'Off',
                    'store_closed' => 'Store Closed',
                    'izin_khusus' => 'Izin Khusus',
                ])
                ->reactive()
                ->required(),
            Select::make('sub_type')
                ->label('Jenis Cuti')
                ->options([
                    'cuti_tahunan' => 'Cuti Tahunan',
                    'cuti_peraturan' => 'Cuti Peraturan',
                ])
                ->visible(fn (Get $get) => $get('type') === 'cuti')
                ->required(fn (Get $get) => $get('type') === 'cuti'),
            DatePicker::make('start_date')
                ->required(),
            DatePicker::make('end_date')
                ->required(),
            Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
            FileUpload::make('attachment_path')
                ->label('Attachment')
                ->disk('public')
                ->directory('leave-attachments')
                ->columnSpanFull(),
            Select::make('status')
                ->options([
                    'pending'  => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->default('pending')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->description(fn (LeaveRequest $record): string => $record->employee?->position?->name ?? '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->searchable(),
                TextColumn::make('sub_type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace('_', ' ', $state)) : '-')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('head_approval_status')
                    ->label('Head Approval')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('hrd_approval_status')
                    ->label('HRD Approval')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Final Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                Action::make('Cetak Surat Cuti')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->visible(function (LeaveRequest $record) {
                        return $record->status === 'approved' && $record->type === 'cuti';
                    })
                    ->action(function (LeaveRequest $record) {
                        $pdf = Pdf::loadView('pdf.surat-cuti', ['record' => $record]);
                        $filename = 'Surat-Cuti-' . str_replace(' ', '-', $record->employee->full_name) . '-' . date('Ymd', strtotime($record->start_date)) . '.pdf';
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $filename);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Permit Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Employee Name'),
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
                        TextEntry::make('sub_type')
                            ->label('Sub Type')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace('_', ' ', $state)) : '-'),
                        TextEntry::make('status')
                            ->label('Final Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending'  => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'gray',
                            }),
                        TextEntry::make('start_date')
                            ->date(),
                        TextEntry::make('end_date')
                            ->date(),
                        TextEntry::make('head_approval_status')
                            ->label('Head Approval')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending'  => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'gray',
                            }),
                        TextEntry::make('head_approval_notes')
                            ->label('Catatan Head')
                            ->visible(fn (?string $state): bool => filled($state)),
                        TextEntry::make('hrd_approval_status')
                            ->label('HRD Approval')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending'  => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'gray',
                            }),
                        TextEntry::make('hrd_approval_notes')
                            ->label('Catatan HRD')
                            ->visible(fn (?string $state): bool => filled($state)),
                        TextEntry::make('notes')
                            ->columnSpanFull(),
                    ]),
                Section::make('Attachment')
                    ->schema([
                        ImageEntry::make('attachment_path')
                            ->hiddenLabel()
                            ->disk('public')
                            ->width('100%')
                            ->height('auto'),
                    ])
                    ->visible(fn ($record) => $record->attachment_path !== null),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        return \App\Traits\ScopesUserData::applyUserAccessScope($query);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Super Admin') || auth()->user()->can('view_leave_requests');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view'   => Pages\ViewLeaveRequest::route('/{record}'),
            'edit'   => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
