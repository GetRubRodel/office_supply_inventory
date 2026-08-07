<div>
    @if ($blocked)
        <div class="rounded-xl bg-warning-50 p-4 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-400/10 dark:ring-warning-500/30">
            <div class="flex gap-3">
                <x-filament::icon
                    icon="heroicon-o-exclamation-triangle"
                    class="h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400"
                />
                <div>
                    <h2 class="text-sm font-semibold text-warning-800 dark:text-warning-200">
                        {{ $noticeTitle }}
                    </h2>
                    <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">
                        {{ $noticeBody }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
