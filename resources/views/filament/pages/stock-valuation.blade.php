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
        <x-filament::section :heading="__('erp.reports.stockValuation')">
            <div class="mb-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                <div>
                    <span class="font-medium">{{ __('erp.reports.totalQuantity') }}</span>
                    {{ number_format((float) $report_data['total_quantity'], 2) }}
                </div>
                <div>
                    <span class="font-medium">{{ __('erp.reports.totalValue') }}</span>
                    {{ number_format((float) $report_data['total_value'], 2) }}
                </div>
            </div>

            @if (count($report_data['rows']) === 0)
                <div class="text-sm text-gray-500">{{ __('erp.reports.noStockValuationRows') }}</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b font-medium">
                            <th class="py-2 text-left">{{ __('erp.reports.sku') }}</th>
                            <th class="py-2 text-left">{{ __('erp.reports.item') }}</th>
                            <th class="py-2 text-left">{{ __('erp.reports.warehouse') }}</th>
                            <th class="py-2 text-right">{{ __('erp.reports.quantity') }}</th>
                            <th class="py-2 text-right">{{ __('erp.reports.avgCost') }}</th>
                            <th class="py-2 text-right">{{ __('erp.reports.value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report_data['rows'] as $row)
                            <tr class="border-b">
                                <td class="py-1">{{ $row['sku'] }}</td>
                                <td class="py-1">{{ $row['item_name'] }}</td>
                                <td class="py-1">{{ $row['warehouse_code'] }} - {{ $row['warehouse_name'] }}</td>
                                <td class="py-1 text-right">{{ number_format((float) $row['quantity'], 2) }}</td>
                                <td class="py-1 text-right">{{ number_format((float) $row['weighted_avg_cost'], 2) }}</td>
                                <td class="py-1 text-right">{{ number_format((float) $row['value'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold">
                            <td class="py-2" colspan="3">{{ __('erp.reports.total') }}</td>
                            <td class="py-2 text-right">{{ number_format((float) $report_data['total_quantity'], 2) }}</td>
                            <td class="py-2"></td>
                            <td class="py-2 text-right">{{ number_format((float) $report_data['total_value'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
