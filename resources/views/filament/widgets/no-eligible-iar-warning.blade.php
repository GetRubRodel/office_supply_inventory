<div class="fi-no-eligible-iar-warning">
    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
        <x-filament::icon
            icon="heroicon-o-exclamation-triangle"
            class="h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400"
        />
        <div>
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                Cannot create BAC Resolution
            </p>
            <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                {{ $message }}
            </p>
        </div>
    </div>
</div>
