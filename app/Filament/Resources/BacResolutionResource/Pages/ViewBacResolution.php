<?php

namespace App\Filament\Resources\BacResolutionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\BacResolutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBacResolution extends ViewRecord
{
    protected static string $resource = BacResolutionResource::class;

    protected ?string $maxContentWidth = '4xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print Resolution')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('bac.print', $this->record))
                ->openUrlInNewTab(),
            Actions\EditAction::make()
                ->visible(fn (): bool =>
                    CurrentUser::get()?->canManageLegal()),
            Actions\DeleteAction::make()
                ->visible(fn (): bool =>
                    CurrentUser::get()?->canManageLegal()),
        ];
    }
}
