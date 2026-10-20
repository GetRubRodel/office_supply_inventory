{{--
    Live preview of the BAC Resolution body inside the Filament form.

    $blocks comes from App\Support\BacResolutionText::blocks() — the exact
    same list the print/download view renders — so the form preview and the
    printed document can never disagree, and no Division or Budget Amount is
    ever hardcoded.
--}}
@php
    $blocks = $blocks ?? [];
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-4 text-sm leading-relaxed text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
    @forelse ($blocks as $block)
        @php
            [$type, $text] = [$block['type'], $block['text']];
        @endphp

        @if ($type === 'title')
            {{-- Sentence case, regular weight — must match the print view.
                 No font-size utility here on purpose: every block (title,
                 WHEREAS, NOW THEREFORE, numbered items and the SO RESOLVED
                 closing) inherits the single `text-sm` set on the wrapper, so
                 the whole resolution renders at one consistent size. --}}
            <p class="mb-3 text-center font-normal tracking-wide">{{ $text }}</p>
        @elseif ($type === 'whereas')
            <p class="mb-2 pl-5 -indent-5 text-justify">{{ $text }}</p>
        @elseif ($type === 'operative')
            <p class="mb-2 text-justify">{{ $text }}</p>
        @elseif ($type === 'item')
            <p class="mb-2 ml-6 -indent-6 text-justify">{{ $text }}</p>
        @elseif ($type === 'closing')
            {{-- Two paragraphs ("SO RESOLVED." / "Done this ...") separated by
                 a blank line — pre-line preserves that break, matching the
                 print view. Not bold. --}}
            <p class="mt-3 whitespace-pre-line text-left font-normal">{{ $text }}</p>
        @else
            <p class="mt-3 font-bold">{{ $text }}</p>
        @endif
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Select a Division, enter a Budget Amount and pick a date to populate the resolution.
        </p>
    @endforelse
</div>
