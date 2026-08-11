<x-filament-panels::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
    </form>

    <div class="mt-8">
        @livewire(\App\Filament\Widgets\ManPowerChartWidget::class, ['year' => $year, 'company_id' => $company_id, 'branch_id' => $branch_id])
    </div>

    <div class="mt-8">
        <div class="fi-ta-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y table-auto fi-ta-table divide-gray-200 dark:divide-white/5" style="width: 100%; border-collapse: collapse;">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th style="padding: 12px 16px; text-align: left;" class="text-sm font-semibold text-gray-950 dark:text-white">Perusahaan</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Jan</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Feb</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Mar</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Apr</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">May</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Jun</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Jul</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Aug</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Sep</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Oct</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Nov</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-semibold text-gray-950 dark:text-white">Dec</th>
                            <th style="padding: 12px 16px; text-align: center;" class="text-sm font-bold text-gray-950 dark:text-white">Avg</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                        @forelse($this->getManPowerData() as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                <td style="padding: 12px 16px;" class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['company'] }}</td>
                                @foreach($row['months'] as $index => $val)
                                    <td style="padding: 12px 16px; text-align: center;" class="text-sm {{ $index == 12 ? 'font-bold text-primary-600' : 'text-gray-500 dark:text-gray-400' }}">{{ $val }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" style="padding: 24px 16px; text-align: center;" class="text-sm text-gray-500">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
