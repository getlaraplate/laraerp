<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}
        <div class="mt-4 flex flex-wrap gap-2">
            <x-filament::button type="submit">
                Generate
            </x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="exportCsv">
                Export CSV
            </x-filament::button>
        </div>
    </form>

    @if (! empty($report_data))
        <x-filament::section :heading="__('erp.reports.pipelineSummary')">
            <div class="mb-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                <div>
                    <span class="font-medium">{{ __('erp.reports.totalPipeline') }}</span>
                    {{ number_format((float) $report_data['total_expected_value_local'], 2) }}
                </div>
                <div>
                    <span class="font-medium">{{ __('erp.reports.wonValue') }}</span>
                    {{ number_format((float) $report_data['won_value_local'], 2) }}
                </div>
                <div>
                    <span class="font-medium">{{ __('erp.reports.lostCount') }}</span>
                    {{ $report_data['lost_count'] }}
                </div>
                <div>
                    <span class="font-medium">{{ __('erp.reports.totalOpportunities') }}</span>
                    {{ $report_data['total_count'] }}
                </div>
                <div>
                    <span class="font-medium">{{ __('erp.reports.won') }}</span>
                    {{ $report_data['won_count'] }}
                </div>
                <div>
                    <span class="font-medium">{{ __('erp.reports.lost') }}</span>
                    {{ $report_data['lost_count'] }}
                </div>
            </div>

            @if ((int) $report_data['total_count'] === 0)
                <div class="text-sm text-gray-500">{{ __('erp.reports.noPipelineData') }}</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b font-medium">
                            <th class="py-2 text-left">{{ __('erp.reports.status') }}</th>
                            <th class="py-2 text-right">{{ __('erp.reports.count') }}</th>
                            <th class="py-2 text-right">{{ __('erp.reports.expectedDoc') }}</th>
                            <th class="py-2 text-right">{{ __('erp.reports.expectedLocal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report_data['by_status'] as $row)
                            <tr class="border-b">
                                <td class="py-1">{{ ucfirst($row['status']) }}</td>
                                <td class="py-1 text-right">{{ $row['count'] }}</td>
                                <td class="py-1 text-right">{{ number_format((float) $row['expected_value_doc'], 2) }}</td>
                                <td class="py-1 text-right">{{ number_format((float) $row['expected_value_local'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold">
                            <td class="py-2">{{ __('erp.reports.total') }}</td>
                            <td class="py-2 text-right">{{ $report_data['total_count'] }}</td>
                            <td class="py-2 text-right">{{ number_format((float) $report_data['total_expected_value_doc'], 2) }}</td>
                            <td class="py-2 text-right">{{ number_format((float) $report_data['total_expected_value_local'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
