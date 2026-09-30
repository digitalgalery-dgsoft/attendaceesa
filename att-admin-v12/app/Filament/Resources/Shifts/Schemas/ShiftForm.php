<?php

namespace App\Filament\Resources\Shifts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('principal_id')
                    ->relationship('principal', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                        $query->where('is_active', true);
                        $user = auth()->user();
                        if ($user) {
                            $isAdmin = method_exists($user, 'isAdministrator')
                                ? $user->isAdministrator()
                                : ($user->isSuperAdmin() || $user->hasRole(['Administrator', 'Admin', 'admin']));

                            if (!$isAdmin) {
                                $principalIds = method_exists($user, 'getAccessiblePrincipalIds')
                                    ? $user->getAccessiblePrincipalIds()
                                    : $user->principals()->pluck('principals.id')->toArray();

                                if (empty($principalIds) && $user->employee && $user->employee->principal_id) {
                                    $principalIds = [(int) $user->employee->principal_id];
                                }

                                if (!empty($principalIds)) {
                                    $query->whereIn('id', $principalIds);
                                } else {
                                    $query->whereRaw('1 = 0');
                                }
                            }
                        }
                        return $query->orderBy('name');
                    })
                    ->default(function () {
                        $user = auth()->user();
                        if ($user) {
                            $isAdmin = method_exists($user, 'isAdministrator')
                                ? $user->isAdministrator()
                                : ($user->isSuperAdmin() || $user->hasRole(['Administrator', 'Admin', 'admin']));

                            if (!$isAdmin) {
                                $principalIds = method_exists($user, 'getAccessiblePrincipalIds')
                                    ? $user->getAccessiblePrincipalIds()
                                    : $user->principals()->pluck('principals.id')->toArray();

                                if (empty($principalIds) && $user->employee && $user->employee->principal_id) {
                                    $principalIds = [(int) $user->employee->principal_id];
                                }

                                return count($principalIds) === 1 ? $principalIds[0] : null;
                            }
                        }
                        return null;
                    })
                    ->label('Prinsiple')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->default(fn () => 'SHF-' . strtoupper(\Illuminate\Support\Str::random(5)))
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->unique(ignoreRecord: true),
                TimePicker::make('start_time')
                    ->required(),
                TimePicker::make('end_time')
                    ->required(),
                TimePicker::make('break_start_time'),
                TimePicker::make('break_end_time'),
                TextInput::make('grace_checkin_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('grace_checkout_minutes')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_cross_day')
                    ->required(),
                Toggle::make('required_checkin')
                    ->required(),
                Toggle::make('required_checkout')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
