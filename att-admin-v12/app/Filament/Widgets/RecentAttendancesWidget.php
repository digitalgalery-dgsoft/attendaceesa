<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Attendance;

class RecentAttendancesWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        $query = Attendance::with(['employee', 'employeeSchedule.shift'])->latest();
        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
        }

        return $table
            ->heading('Recent Attendances')
            ->query($query->limit(5))
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('employeeSchedule.shift.name')
                    ->label('Shift')
                    ->badge()
                    ->default('-')
                    ->color('info'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Terlambat' => 'warning',
                        'Izin' => 'info',
                        'Sakit' => 'danger',
                        'Alpha' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
