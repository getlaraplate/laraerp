<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\BankStatements\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Modules\ERP\Filament\Resources\BankStatements\BankStatementResource;
use Modules\ERP\Models\BankStatement;
use Modules\ERP\Services\Banking\BankStatementCsvImporter;
use Modules\ERP\Services\Banking\BankStatementImportService;
use Override;

final class EditBankStatement extends EditRecord
{
    use HasCloseOrCancelFormAction;

    #[Override]
    protected static string $resource = BankStatementResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_file')
                ->label(__('erp.actions.importFile'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->form([
                    FileUpload::make('file')
                        ->label(__('erp.fields.statement_file'))
                        ->disk('local')
                        ->directory('erp-bank-statements')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'text/xml', 'application/xml'])
                        ->required(),
                    Select::make('format')
                        ->options([
                            'auto' => __('erp.help.autoDetect'),
                            'csv' => 'CSV',
                            'camt053' => 'CAMT.053',
                            'mt940' => 'MT940',
                        ])
                        ->default('auto')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var BankStatement $statement */
                    $statement = $this->record;
                    $path = Storage::disk('local')->path($data['file']);
                    $format = (string) ($data['format'] ?? 'auto');
                    $count = $format === 'csv'
                        ? app(BankStatementCsvImporter::class)->import($statement, $path)
                        : app(BankStatementImportService::class)->importFile($statement, $path, $format);
                    $this->record->refresh();

                    Notification::make()
                        ->title(__('erp.messages.importedStatementLines', ['count' => $count]))
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
