<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->relationship('employee', 'first_name')
                ->searchable()
                ->required(),
            Select::make('type')
                ->options([
                    'annual_leave'  => 'Annual Leave',
                    'medical_leave' => 'Medical Leave',
                    'permission'    => 'Permission',
                    'shift_swap'    => 'Shift Swap',
                    'extra_off'     => 'Extra Off',
                    'store_closed'  => 'Store Closed',
                ])
                ->required(),
            DatePicker::make('start_date')
                ->required(),
            DatePicker::make('end_date')
                ->required(),
            Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
            FileUpload::make('attachment_path')
                ->label('Attachment')
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
                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
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
                EditAction::make(),
                Action::make('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record) => $record->status === 'pending')
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'status'      => 'approved',
                        'approved_by' => auth()->id(),
                    ])),
                Action::make('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record) => $record->status === 'pending')
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'status'      => 'rejected',
                        'approved_by' => auth()->id(),
                    ])),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit'   => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
