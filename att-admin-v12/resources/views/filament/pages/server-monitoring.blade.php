<x-filament-panels::page>
    <div class="-m-4 sm:-m-6">
        @include('admin.server_monitoring', [
            'initialData' => $initialData,
            'definitions' => $definitions,
            'activeNode' => $activeNode ?? 'all',
            'isStandalone' => false,
        ])
    </div>
</x-filament-panels::page>
