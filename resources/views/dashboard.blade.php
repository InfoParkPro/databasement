<x-layouts.app :title="__('Dashboard')">
    @php $patternId = uniqid('pattern-'); @endphp
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <svg class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" fill="none">
                    <defs>
                        <pattern id="{{ $patternId }}-1" x="0" y="0" width="8" height="8" patternUnits="userSpaceOnUse">
                            <path d="M-1 5L5 -1M3 9L8.5 3.5" stroke-width="0.5"></path>
                        </pattern>
                    </defs>
                    <rect stroke="none" fill="url(#{{ $patternId }}-1)" width="100%" height="100%"></rect>
                </svg>
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <svg class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" fill="none">
                    <defs>
                        <pattern id="{{ $patternId }}-2" x="0" y="0" width="8" height="8" patternUnits="userSpaceOnUse">
                            <path d="M-1 5L5 -1M3 9L8.5 3.5" stroke-width="0.5"></path>
                        </pattern>
                    </defs>
                    <rect stroke="none" fill="url(#{{ $patternId }}-2)" width="100%" height="100%"></rect>
                </svg>
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <svg class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" fill="none">
                    <defs>
                        <pattern id="{{ $patternId }}-3" x="0" y="0" width="8" height="8" patternUnits="userSpaceOnUse">
                            <path d="M-1 5L5 -1M3 9L8.5 3.5" stroke-width="0.5"></path>
                        </pattern>
                    </defs>
                    <rect stroke="none" fill="url(#{{ $patternId }}-3)" width="100%" height="100%"></rect>
                </svg>
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <svg class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" fill="none">
                <defs>
                    <pattern id="{{ $patternId }}-4" x="0" y="0" width="8" height="8" patternUnits="userSpaceOnUse">
                        <path d="M-1 5L5 -1M3 9L8.5 3.5" stroke-width="0.5"></path>
                    </pattern>
                </defs>
                <rect stroke="none" fill="url(#{{ $patternId }}-4)" width="100%" height="100%"></rect>
            </svg>
        </div>
    </div>
</x-layouts.app>
