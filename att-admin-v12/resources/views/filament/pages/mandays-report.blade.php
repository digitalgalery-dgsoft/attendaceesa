<x-filament-panels::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
    </form>

    <div class="mt-8">
        @livewire(\App\Filament\Widgets\MandaysChartWidget::class, ['month' => $month, 'year' => $year, 'branch_id' => $branch_id, 'principal_id' => $principal_id])
    </div>

    <div class="mt-8">
        <div class="fi-ta-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y table-auto fi-ta-table divide-gray-200 dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Nama Karyawan</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Region / Area</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Principal</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Target HK</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Aktual HK</th>
                            <th class="px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">Pencapaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($this->getMandaysData() as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $row['employee'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row['branch'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row['principal'] }}</td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-300">{{ $row['target'] }}</td>
                                <td class="px-4 py-3 text-sm font-bold text-primary-600">{{ $row['aktual'] }}</td>
                                <td class="px-4 py-3 text-sm font-bold {{ $row['percentage'] >= 100 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $row['percentage'] }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
