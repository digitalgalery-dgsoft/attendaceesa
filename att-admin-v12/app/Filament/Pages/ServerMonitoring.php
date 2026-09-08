<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use App\Services\ServerTelemetryService;

class ServerMonitoring extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';
    protected static ?string $navigationLabel = 'Server Monitoring (3 Node)';
    protected static ?string $title = 'Server Monitoring - 3 Server Production';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.server-monitoring';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (method_exists($user, 'can') && ($user->can('manage_settings') || $user->can('view_settings'))) {
            return true;
        }

        return in_array($user->role ?? '', ['admin', 'superadmin']);
    }

    public function getViewData(): array
    {
        return [
            'initialData' => ServerTelemetryService::getAllNodesMetrics(),
            'definitions' => ServerTelemetryService::getNodeDefinitions(),
            'activeNode' => request()->query('node', 'all'),
            'isStandalone' => false,
        ];
    }
}
