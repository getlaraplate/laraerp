<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\Companies\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Modules\ERP\Filament\Resources\Companies\CompanyResource;
use Modules\ERP\Models\Company;
use Override;

final class EditCompany extends EditRecord
{
    use HasCloseOrCancelFormAction;

    #[Override]
    protected static string $resource = CompanyResource::class;

    /**
     * @return array<Action>
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('linkCounterparty')
                ->label(__('erp.actions.linkCounterparty'))
                ->visible(fn (): bool => $this->getRecord()->counterparty_id === null)
                ->action(function (): void {
                    /** @var Company $company */
                    $company = $this->getRecord();
                    $company->ensureCounterparty();
                    $this->refreshFormData(['counterparty_id']);
                }),
        ];
    }
}
