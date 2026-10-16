<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\Invoices\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Modules\ERP\Casts\EInvoiceSubmissionStatus;
use Modules\ERP\Casts\InvoiceDirection;
use Modules\ERP\Casts\InvoiceType;
use Modules\ERP\Filament\Resources\Invoices\Actions\InvoicePostingActions;
use Modules\ERP\Filament\Resources\Invoices\InvoiceResource;
use Modules\ERP\Models\DeliveryNoteLine;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\InvoiceLine;
use Modules\ERP\Services\Accounting\CreditNoteService;
use Modules\ERP\Services\EInvoice\EInvoiceSubmissionService;
use Override;

final class EditInvoice extends EditRecord
{
    use HasCloseOrCancelFormAction;

    #[Override]
    protected static string $resource = InvoiceResource::class;

    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->record;
        $data['line_items'] = $invoice->lines()
            ->with('delivery_note_lines')
            ->orderBy('line_no')
            ->get()
            ->map(static fn (InvoiceLine $line): array => [
                'sales_order_line_id' => $line->sales_order_line_id,
                'purchase_order_line_id' => $line->purchase_order_line_id,
                'goods_receipt_line_id' => $line->goods_receipt_line_id,
                'match_status' => $line->match_status?->value,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_code_id' => $line->tax_code_id,
                'delivery_note_line_links' => $line->delivery_note_lines
                    ->map(static fn (DeliveryNoteLine $dn_line): array => [
                        'delivery_note_line_id' => $dn_line->id,
                        'quantity' => $dn_line->pivot->quantity,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        return $data;
    }

    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $line_items = $data['line_items'] ?? [];
        unset($data['line_items']);

        /** @var Invoice $record */
        if ($record->journal_entry_id !== null) {
            unset($data['line_items']);
            $record->update($data);

            return $record;
        }

        $record->update($data);
        $record->lines()->delete();

        foreach (array_values($line_items) as $index => $line) {
            $payload = Arr::only($line, [
                'sales_order_line_id',
                'purchase_order_line_id',
                'goods_receipt_line_id',
                'description',
                'quantity',
                'unit_price',
                'tax_code_id',
            ]);
            $payload['line_no'] = $index + 1;

            /** @var InvoiceLine $invoice_line */
            $invoice_line = $record->lines()->create($payload);
            $this->syncDeliveryNoteLineLinks($invoice_line, $line);
        }

        return $record;
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label(__('erp.actions.post'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('erp.messages.postInvoice'))
                ->modalDescription(__('erp.messages.postInvoiceDescription'))
                ->authorize(fn (): bool => auth()->user()?->can('post', $this->record) ?? false)
                ->visible(fn (): bool => $this->record->journal_entry_id === null)
                ->form(fn (): array => $this->record->direction === InvoiceDirection::Purchase
                    && (auth()->user()?->can('forcePost', $this->record) ?? false)
                    ? [
                        Checkbox::make('force_three_way_match')
                            ->helperText(__('erp.help.forceThreeWayMatch')),
                    ]
                    : [])
                ->action(function (array $data): void {
                    InvoicePostingActions::postInvoice(
                        $this->record,
                        (bool) ($data['force_three_way_match'] ?? false),
                    );
                    $this->refreshFormData(['reference', 'posted_at_display']);
                }),
            Action::make('unpost')
                ->label(__('erp.actions.unpost'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('erp.messages.unpostInvoice'))
                ->modalDescription(__('erp.messages.unpostInvoiceDescription'))
                ->authorize(fn (): bool => auth()->user()?->can('unpost', $this->record) ?? false)
                ->visible(fn (): bool => $this->record->journal_entry_id !== null)
                ->action(function (): void {
                    InvoicePostingActions::unpostInvoice($this->record);
                    $this->refreshFormData(['reference', 'posted_at_display']);
                }),
            Action::make('create_credit_note')
                ->label(__('erp.actions.createCreditNote'))
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->journal_entry_id !== null
                    && $this->record->invoice_type === InvoiceType::Invoice)
                ->action(function (): void {
                    $service = resolve(CreditNoteService::class);
                    $credit_note = $service->createFromInvoice($this->record);
                    $this->redirect(InvoiceResource::getUrl('edit', ['record' => $credit_note]));
                }),
            Action::make('send_einvoice')
                ->label(__('erp.actions.sendEInvoice'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary')
                ->requiresConfirmation()
                ->authorize(fn (): bool => auth()->user()?->can('submitEInvoice', $this->record) ?? false)
                ->visible(fn (): bool => $this->record->posted_at !== null
                    && $this->record->direction === InvoiceDirection::Sale
                    && ! $this->record->eInvoiceSubmissions()
                        ->whereIn('status', [
                            EInvoiceSubmissionStatus::Submitted->value,
                            EInvoiceSubmissionStatus::Accepted->value,
                        ])
                        ->exists())
                ->action(function (): void {
                    $service = resolve(EInvoiceSubmissionService::class);
                    $submission = $service->submit($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title(__('erp.messages.eInvoiceSubmitted', ['provider' => $submission->provider_code, 'id' => $submission->external_id ?? __('erp.messages.pending')]))
                        ->success()
                        ->send();
                }),
            Action::make('refresh_einvoice_status')
                ->label(__('erp.actions.refreshEInvoice'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->authorize(fn (): bool => auth()->user()?->can('refreshEInvoice', $this->record) ?? false)
                ->visible(fn (): bool => $this->record->eInvoiceSubmissions()
                    ->where('status', EInvoiceSubmissionStatus::Submitted->value)
                    ->whereNotNull('external_id')
                    ->exists())
                ->action(function (): void {
                    $submission = $this->record->eInvoiceSubmissions()
                        ->where('status', EInvoiceSubmissionStatus::Submitted->value)
                        ->whereNotNull('external_id')
                        ->latest('id')
                        ->firstOrFail();

                    $service = resolve(EInvoiceSubmissionService::class);
                    $service->refresh($submission);
                    $this->record->refresh();

                    Notification::make()
                        ->title(__('erp.messages.eInvoiceStatusRefreshed'))
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $line_data
     */
    private function syncDeliveryNoteLineLinks(InvoiceLine $invoice_line, array $line_data): void
    {
        $links = $line_data['delivery_note_line_links'] ?? [];
        $pivot = [];

        foreach ($links as $link) {
            $delivery_note_line_id = $link['delivery_note_line_id'] ?? null;

            if ($delivery_note_line_id === null || $delivery_note_line_id === '') {
                continue;
            }

            $pivot[(int) $delivery_note_line_id] = ['quantity' => number_format((float) $link['quantity'], 4, '.', '')];
        }

        $invoice_line->delivery_note_lines()->sync($pivot);
    }
}
