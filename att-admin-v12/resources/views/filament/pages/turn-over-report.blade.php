<x-filament-panels::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
    </form>

    <div class="mt-8">
        @livewire(\App\Filament\Widgets\TurnOverChartWidget::class, ['year' => $year, 'company_id' => $company_id])
    </div>

    <div class="mt-8">
        <div class="fi-ta-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y table-auto fi-ta-table divide-gray-200 dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Bulan</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Masuk (Join)</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Keluar (Resign)</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Net Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($this->getTurnOverData() as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $row['month'] }}</td>
                                <td class="px-4 py-3 text-sm text-green-600 font-bold">+{{ $row['joined'] }}</td>
                                <td class="px-4 py-3 text-sm text-red-600 font-bold">-{{ $row['resigned'] }}</td>
                                <td class="px-4 py-3 text-sm font-bold {{ $row['net'] > 0 ? 'text-green-600' : ($row['net'] < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                    {{ $row['net'] > 0 ? '+' : '' }}{{ $row['net'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
