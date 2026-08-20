<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('branches.name')
                    ->label('Area / Cabang')
                    ->badge()
                    ->color('info')
                    ->placeholder('Semua Area')
                    ->searchable(),
                TextColumn::make('principals.name')
                    ->label('Prinsiple')
                    ->badge()
                    ->color('success')
                    ->placeholder('Semua Prinsiple')
                    ->searchable(),
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
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
                \Filament\Actions\Action::make('impersonate')
                    ->label('Switch Akun')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Switch ke Akun User')
                    ->modalDescription(fn ($record) => "Anda akan login dan melihat sistem sebagai {$record->name} ({$record->email}). Anda dapat kembali ke akun Super Admin kapan saja.")
                    ->modalSubmitActionLabel('Ya, Switch Sekarang')
                    ->visible(fn ($record) => auth()->check() && auth()->user()->isSuperAdmin() && auth()->id() !== $record->id && !session()->has('impersonated_by'))
                    ->url(fn ($record) => route('impersonation.start', ['user' => $record->id])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
