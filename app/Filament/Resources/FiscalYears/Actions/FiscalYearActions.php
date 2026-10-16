<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\FiscalYears\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Modules\ERP\Models\FiscalYear;
use Modules\ERP\Services\Accounting\FiscalPeriodCloser;

final class FiscalYearActions
{
    public static function close(): Action
    {
        return Action::make('close_year')
            ->label(__('erp.actions.closeFiscalYear'))
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('erp.messages.closeFiscalYearDescription'))
            ->authorize(static fn (FiscalYear $record): bool => auth()->user()?->can('close', $record) ?? false)
            ->visible(static fn (FiscalYear $record): bool => ! $record->is_closed)
            ->action(static function (FiscalYear $record): void {
                resolve(FiscalPeriodCloser::class)->closeYear($record);

                Notification::make()
                    ->title(__('erp.messages.fiscalYearClosed'))
                    ->success()
                    ->send();
            });
    }
}
