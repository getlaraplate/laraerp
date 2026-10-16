<?php

declare(strict_types=1);

namespace Modules\ERP\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Modules\Core\Filament\Utils\HasForm;

final class TaskForm
{
    use HasForm;

    public static function configure(Schema $schema): Schema
    {
        self::configureForm($schema);

        return $schema->components([
            Select::make('project_id')->relationship('project', 'name')->searchable()->preload(),
            Select::make('site_id')->relationship('site', 'name')->searchable()->preload(),
            Select::make('taxonomy_id')->relationship('taxonomy', 'name')->searchable()->preload()->required(),
            DateTimePicker::make('valid_from')->label(__('erp.fields.starts_at'))->required(),
            DateTimePicker::make('valid_to')->label(__('erp.fields.ends_at'))->afterOrEqual('valid_from'),
        ]);
    }
}
