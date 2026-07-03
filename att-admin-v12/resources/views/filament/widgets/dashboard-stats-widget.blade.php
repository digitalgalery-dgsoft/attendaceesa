<x-filament-widgets::widget>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Employees -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-primary-50 dark:bg-primary-500/10 rounded-lg text-primary-600 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-m-users" class="w-6 h-6" />
                </div>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider">Total Employees</p>
                <p class="text-3xl font-bold mt-1 text-gray-950 dark:text-white">{{ $totalEmployees }}</p>
            </div>
        </div>

        <!-- Present Today -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-success-50 dark:bg-success-500/10 rounded-lg text-success-600 dark:text-success-400">
                    <x-filament::icon icon="heroicon-m-check-badge" class="w-6 h-6" />
                </div>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider">Present Today</p>
                <p class="text-3xl font-bold mt-1 text-gray-950 dark:text-white">{{ $attendancesToday }}</p>
            </div>
        </div>

        <!-- Total Companies -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-info-50 dark:bg-info-500/10 rounded-lg text-info-600 dark:text-info-400">
                    <x-filament::icon icon="heroicon-m-building-office-2" class="w-6 h-6" />
                </div>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider">Total Companies</p>
                <p class="text-3xl font-bold mt-1 text-gray-950 dark:text-white">{{ $totalCompanies }}</p>
            </div>
        </div>

        <!-- Total Branches -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-warning-50 dark:bg-warning-500/10 rounded-lg text-warning-600 dark:text-warning-400">
                    <x-filament::icon icon="heroicon-m-map-pin" class="w-6 h-6" />
                </div>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider">Total Branches</p>
                <p class="text-3xl font-bold mt-1 text-gray-950 dark:text-white">{{ $totalBranches }}</p>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
