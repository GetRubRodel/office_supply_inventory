<?php

namespace App\Filament\Resources\BacResolutionResource\Pages;

use App\Filament\Resources\BacResolutionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateBacResolution extends CreateRecord
{
    protected static string $resource = BacResolutionResource::class;

    protected ?string $maxContentWidth = '4xl';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle record creation with retry logic for unique constraint violations.
     *
     * Catches database-level race conditions where two concurrent requests
     * generate the same resolution_no. On failure, retries with a freshly
     * generated number.
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $maxAttempts = 5;
        $attempt = 0;

        do {
            $attempt++;

            try {
                return static::getModel()::create($data);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= $maxAttempts) {
                    Notification::make()
                        ->danger()
                        ->title('Failed to create BAC Resolution')
                        ->body('Unable to generate a unique resolution number after multiple attempts. Please try again.')
                        ->persistent()
                        ->send();

                    throw $e;
                }

                // Brief pause to let the other transaction complete,
                // then retry — the creating event will generate a fresh number.
                usleep(200_000);
            }
        } while (true);
    }
}
