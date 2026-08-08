<?php

namespace App\Filament\Resources\BacResolutionResource\Pages;

use App\Filament\Resources\BacResolutionResource;
use App\Models\BacResolution;
use App\Models\InspectionAcceptanceReport;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBacResolution extends CreateRecord
{
    protected static string $resource = BacResolutionResource::class;

    protected ?string $maxContentWidth = '4xl';

    /**
     * Prevent direct access (URL / API / navigation) to the Create page when
     * there is no eligible IAR available as the source document.
     */
    public function mount(): void
    {
        if (! BacResolutionResource::hasEligibleIar()) {
            Notification::make()
                ->warning()
                ->title('Cannot create BAC Resolution')
                ->body(BacResolution::NO_ELIGIBLE_IAR_MESSAGE)
                ->persistent()
                ->send();

            $this->redirect(BacResolutionResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Create the BAC Resolution inside a transaction, locking the source IAR
     * row so two concurrent submissions can never convert the same IAR twice.
     *
     * On database-level unique constraint violations the retry logic only
     * applies to resolution_no collisions; an already-converted IAR (unique
     * iar_id index) fails fast with a clear validation error.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $maxAttempts = 5;
        $attempt = 0;

        do {
            $attempt++;

            try {
                return DB::transaction(function () use ($data) {
                    /** @var InspectionAcceptanceReport|null $iar */
                    $iar = InspectionAcceptanceReport::query()
                        ->whereKey($data['iar_id'] ?? null)
                        ->lockForUpdate()
                        ->first();

                    if (! $iar || $iar->isConvertedToBac()) {
                        throw ValidationException::withMessages([
                            'iar_id' => BacResolution::NO_ELIGIBLE_IAR_MESSAGE,
                        ]);
                    }

                    $record = static::getModel()::create($data);

                    if (! $record->iar_id) {
                        throw ValidationException::withMessages([
                            'iar_id' => 'A BAC Resolution must be created from an existing Inspection and Acceptance Report (IAR).',
                        ]);
                    }

                    return $record;
                });
            } catch (UniqueConstraintViolationException $e) {
                // Another BAC Resolution already converted this IAR (unique iar_id index).
                if (isset($data['iar_id']) && static::getModel()::where('iar_id', $data['iar_id'])->exists()) {
                    Notification::make()
                        ->danger()
                        ->title('Cannot create BAC Resolution')
                        ->body('This Inspection and Acceptance Report (IAR) has already been converted into a BAC Resolution.')
                        ->persistent()
                        ->send();

                    throw ValidationException::withMessages([
                        'iar_id' => 'This Inspection and Acceptance Report (IAR) has already been converted into a BAC Resolution.',
                    ]);
                }

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
