<?php

namespace App\Filament\Resources\SupplyResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\SupplyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupply extends EditRecord
{
    protected static string $resource = SupplyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if (! CurrentUser::get()?->canManageInventory()) {
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => CurrentUser::get()?->hasPermissionTo('supplies.delete')),
        ];
    }
}
