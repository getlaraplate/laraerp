<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\Invoices\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Modules\ERP\Casts\InvoiceDirection;
use Modules\ERP\Models\Invoice;

final class InvoicePostingActions
{
    public static function post(): Action
    {
        return Action::make('post')
            ->label(__('erp.actions.post'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('erp.messages.postInvoice'))
            ->modalDescription(__('erp.messages.postInvoiceDescription'))
            ->authorize(static fn (Invoice $record): bool => auth()->user()?->can('post', $record) ?? false)
            ->visible(static fn (Invoice $record): bool => $record->journal_entry_id === null)
            ->form(static fn (Invoice $record): array => $record->direction === InvoiceDirection::Purchase
                && (auth()->user()?->can('forcePost', $record) ?? false)
                ? [
                    Checkbox::make('force_three_way_match')
                        ->helperText(__('erp.help.forceThreeWayMatch')),
                ]
                : [])
            ->action(static function (Invoice $record, array $data): void {
                self::postInvoice($record, (bool) ($data['force_three_way_match'] ?? false));
            });
    }

    public static function unpost(): Action
    {
        return Action::make('unpost')
            ->label(__('erp.actions.unpost'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('erp.messages.unpostInvoice'))
            ->modalDescription(__('erp.messages.unpostInvoiceDescription'))
            ->authorize(static fn (Invoice $record): bool => auth()->user()?->can('unpost', $record) ?? false)
            ->visible(static fn (Invoice $record): bool => $record->journal_entry_id !== null)
            ->action(static function (Invoice $record): void {
                self::unpostInvoice($record);
            });
    }

    public static function postInvoice(Invoice $invoice, bool $force_three_way_match = false): void
    {
        $invoice->forceThreeWayMatchOnPosting = $force_three_way_match;
        $invoice->update(['posted_at' => now()]);

        Notification::make()
            ->title(__('erp.messages.invoicePosted'))
            ->body(__('erp.messages.reference', ['reference' => $invoice->fresh()->reference ?? '—']))
            ->success()
            ->send();
    }

    public static function unpostInvoice(Invoice $invoice): void
    {
        $invoice->update(['posted_at' => null]);

        Notification::make()
            ->title(__('erp.messages.invoiceUnposted'))
            ->success()
            ->send();
    }
}
