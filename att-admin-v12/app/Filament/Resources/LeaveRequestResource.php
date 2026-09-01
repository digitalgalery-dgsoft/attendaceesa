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
use Filament\Infolists\Components\ViewEntry;
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
    protected static ?int $navigationSort = 4;
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
                ->reactive()
                ->visible(fn (Get $get) => $get('type') === 'cuti')
                ->required(fn (Get $get) => $get('type') === 'cuti'),
            Select::make('cuti_peraturan_type')
                ->label('Kategori Cuti Peraturan')
                ->options([
                    'cuti_menikah'              => 'Cuti Menikah (Pernikahan Sendiri) - Maks 3 Hari',
                    'cuti_menikahkan'           => 'Cuti Menikahkan / Khitan / Baptis Anak - Maks 2 Hari',
                    'cuti_istri_melahirkan'     => 'Cuti Istri Melahirkan / Keguguran - Maks 2 Hari',
                    'cuti_kematian_inti'        => 'Cuti Kematian (Suami/Istri/Anak/Ortu/Mertua) - Maks 2 Hari',
                    'cuti_kematian_serumah'     => 'Cuti Kematian (Anggota Keluarga Serumah) - Maks 1 Hari',
                    'cuti_melahirkan'           => 'Cuti Melahirkan (Prinsiple) - Maks 90 Hari',
                ])
                ->visible(fn (Get $get) => $get('type') === 'cuti' && $get('sub_type') === 'cuti_peraturan')
                ->required(fn (Get $get) => $get('type') === 'cuti' && $get('sub_type') === 'cuti_peraturan'),
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
                    ->formatStateUsing(function (?string $state, LeaveRequest $record): string {
                        if ($record->cuti_peraturan_type) {
                            return match ($record->cuti_peraturan_type) {
                                'cuti_menikah'          => 'Cuti Menikah',
                                'cuti_menikahkan'       => 'Cuti Menikahkan Anak',
                                'cuti_istri_melahirkan' => 'Cuti Istri Melahirkan',
                                'cuti_kematian_inti'    => 'Cuti Kematian Inti',
                                'cuti_kematian_serumah' => 'Cuti Kematian Serumah',
                                'cuti_melahirkan'       => 'Cuti Melahirkan',
                                default                 => ucwords(str_replace('_', ' ', $record->cuti_peraturan_type)),
                            };
                        }
                        return $state ? ucwords(str_replace('_', ' ', $state)) : '-';
                    })
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
                        TextEntry::make('cuti_peraturan_type')
                            ->label('Jenis Cuti Peraturan')
                            ->badge()
                            ->color('info')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'cuti_menikah'          => 'Cuti Menikah (Pernikahan Sendiri)',
                                'cuti_menikahkan'       => 'Cuti Menikahkan / Khitan / Baptis Anak',
                                'cuti_istri_melahirkan' => 'Cuti Istri Melahirkan / Keguguran',
                                'cuti_kematian_inti'    => 'Cuti Kematian (Suami/Istri/Anak/Ortu/Mertua)',
                                'cuti_kematian_serumah' => 'Cuti Kematian (Anggota Keluarga Serumah)',
                                'cuti_melahirkan'       => 'Cuti Melahirkan (Prinsiple)',
                                default                 => $state ? ucwords(str_replace('_', ' ', $state)) : '-',
                            })
                            ->visible(fn ($record) => filled($record->cuti_peraturan_type)),
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
                        ViewEntry::make('attachment_path')
                            ->hiddenLabel()
                            ->view('filament.components.permit-attachment'),
                    ])
                    ->visible(fn ($record) => !empty($record->attachment_path)),
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
