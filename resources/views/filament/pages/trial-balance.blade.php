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

    @if (count($report_data) > 0)
        <x-filament::section :heading="__('erp.reports.trialBalance')">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b font-medium">
                        <th class="text-left py-2">{{ __('erp.reports.code') }}</th>
                        <th class="text-left py-2">{{ __('erp.reports.account') }}</th>
                        <th class="text-left py-2">{{ __('erp.reports.kind') }}</th>
                        <th class="text-right py-2">{{ __('erp.reports.debit') }}</th>
                        <th class="text-right py-2">{{ __('erp.reports.credit') }}</th>
                        <th class="text-right py-2">{{ __('erp.reports.balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report_data as $row)
                        <tr class="border-b">
                            <td class="py-1">{{ $row['account_code'] }}</td>
                            <td class="py-1">{{ $row['account_name'] }}</td>
                            <td class="py-1">{{ $row['account_kind'] }}</td>
                            <td class="py-1 text-right">{{ number_format((float) $row['debit'], 2) }}</td>
                            <td class="py-1 text-right">{{ number_format((float) $row['credit'], 2) }}</td>
                            <td class="py-1 text-right font-medium">{{ number_format((float) $row['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 font-bold">
                        <td class="py-2" colspan="3">{{ __('erp.reports.total') }}</td>
                        <td class="py-2 text-right">{{ number_format(collect($report_data)->sum(fn ($r) => (float) $r['debit']), 2) }}</td>
                        <td class="py-2 text-right">{{ number_format(collect($report_data)->sum(fn ($r) => (float) $r['credit']), 2) }}</td>
                        <td class="py-2 text-right">{{ number_format(collect($report_data)->sum(fn ($r) => (float) $r['balance']), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
