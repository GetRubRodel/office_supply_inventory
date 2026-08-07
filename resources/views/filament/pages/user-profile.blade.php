<x-filament-panels::page>
    <x-filament-panels::form>
        {{ $this->form }}

        <div class="flex justify-end gap-3">
            <x-filament::button
                wire:click="save"
                color="primary"
                size="lg"
                icon="heroicon-m-check"
            >
                Save Changes
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
