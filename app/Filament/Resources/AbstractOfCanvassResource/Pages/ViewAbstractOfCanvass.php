<?php

namespace App\Filament\Resources\AbstractOfCanvassResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\AbstractOfCanvassResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAbstractOfCanvass extends ViewRecord
{
    protected static string $resource = AbstractOfCanvassResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print ABC')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('abc.print', $this->record))
                ->openUrlInNewTab(),
            Actions\EditAction::make()
                ->visible(fn (): bool =>
                    CurrentUser::get()?->canManageProcurement()),
            Actions\DeleteAction::make()
                ->visible(fn (): bool =>
                    CurrentUser::get()?->canManageProcurement()),
        ];
    }
}
