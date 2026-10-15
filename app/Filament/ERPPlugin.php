<?php

declare(strict_types=1);

namespace Modules\ERP\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\ModuleColor;

final class ERPPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'ERP';
    }

    public function getId(): string
    {
        return 'erp';
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Own the module's presence in the panel instead of having the panel list every
     * module: the navigation group, its icon and the module colour (registered under the
     * module id, so widgets can paint with it) are all declared here.
     */
    public function afterRegister(Panel $panel): void
    {
        $color = ModuleColor::filament($this->getModuleName());

        if ($color !== null) {
            $panel->colors([$this->getId() => $color]);
        }

        $panel->navigationGroups([
            'erp.navigation.master_data' => NavigationGroup::make()->label(static fn (): string => (string) __('erp.navigation.master_data'))->icon(Heroicon::OutlinedBuildingOffice),
            'erp.navigation.sales' => NavigationGroup::make()->label(static fn (): string => (string) __('erp.navigation.sales'))->icon(Heroicon::OutlinedShoppingCart),
            'erp.navigation.purchasing' => NavigationGroup::make()->label(static fn (): string => (string) __('erp.navigation.purchasing'))->icon(Heroicon::OutlinedTruck),
            'erp.navigation.inventory' => NavigationGroup::make()->label(static fn (): string => (string) __('erp.navigation.inventory'))->icon(Heroicon::OutlinedArchiveBox),
            'erp.navigation.accounting' => NavigationGroup::make()->label(static fn (): string => (string) __('erp.navigation.accounting'))->icon(Heroicon::OutlinedCalculator),
            'erp.navigation.treasury' => NavigationGroup::make()->label(static fn (): string => (string) __('erp.navigation.treasury'))->icon(Heroicon::OutlinedBanknotes),
        ]);
    }
}
