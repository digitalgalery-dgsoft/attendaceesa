<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Carbon\Carbon;

class DashboardHeaderWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-header-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        return [
            'date' => Carbon::now()->format('M d, Y'),
            'time' => Carbon::now()->format('h:i A'),
        ];
    }
}
