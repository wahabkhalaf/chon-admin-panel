<div class="space-y-2">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold tracking-tight">
            Questions for {{ $competition->name }}
        </h2>

        <div class="flex gap-2 items-center">
            <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                Status: <span class="ml-1 capitalize">{{ $competition->getStatus() }}</span>
            </span>

            @if ($competition->isUpcoming())
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Can modify questions
                </span>
            @else
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    Read-only mode
                </span>
            @endif
        </div>
    </div>

    <div class="max-w-xl mb-2">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Competition opens: <span
                        class="font-medium">{{ $competition->open_time->format('M d, Y H:i') }}</span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Competition starts: <span
                        class="font-medium">{{ $competition->start_time->format('M d, Y H:i') }}</span>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Questions attached: <span class="font-medium">{{ $competition->questions()->count() }}</span>
                </p>
            </div>
        </div>
    </div>
</div>
