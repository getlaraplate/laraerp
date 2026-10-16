<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\SalesOrders\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Modules\ERP\Casts\SalesOrderStatus;
use Modules\ERP\Filament\Resources\SalesOrders\SalesOrderResource;
use Modules\ERP\Models\SalesOrder;
use Modules\ERP\Services\SalesOrders\SalesOrderAmendmentService;

final class SalesOrderAmendmentActions
{
    public static function amend(): Action
    {
        return Action::make('amend')
            ->label(__('erp.actions.createAmendment'))
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('primary')
            ->requiresConfirmation()
            ->modalDescription(__('erp.messages.createAmendmentDescription'))
            ->authorize(static fn (SalesOrder $record): bool => auth()->user()?->can('amend', $record) ?? false)
            ->visible(static fn (SalesOrder $record): bool => in_array($record->status, [
                SalesOrderStatus::Confirmed,
                SalesOrderStatus::PartiallyEvased,
            ], true))
            ->action(static function (SalesOrder $record, Action $action): void {
                $amendment = resolve(SalesOrderAmendmentService::class)->amend($record);

                Notification::make()
                    ->title(__('erp.messages.amendmentCreated'))
                    ->body(__('erp.messages.reference', ['reference' => $amendment->reference ?? '—']))
                    ->success()
                    ->send();

                $action->redirect(SalesOrderResource::getUrl('edit', ['record' => $amendment]));
            });
    }
}
