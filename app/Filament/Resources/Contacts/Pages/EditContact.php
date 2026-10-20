<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\Contacts\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Modules\CRM\Models\Contact;
use Modules\ERP\Filament\Resources\Contacts\ContactResource;
use Override;

final class EditContact extends EditRecord
{
    use HasCloseOrCancelFormAction;

    #[Override]
    protected static string $resource = ContactResource::class;

    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Contact $contact */
        $contact = $this->record;
        $data['counterparty_ids'] = $contact->counterparties()->pluck('id')->all();

        return $data;
    }

    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $counterparty_ids = $data['counterparty_ids'] ?? [];
        unset($data['counterparty_ids']);

        /** @var Contact $record */
        $record->update($data);
        $record->counterparties()->sync($counterparty_ids);

        return $record;
    }
}
