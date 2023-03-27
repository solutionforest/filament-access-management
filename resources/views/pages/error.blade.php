@props([
    'code' => null,
    'errorMessage' => null,
])
<x-filament::page class="filament-error-page">
    <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                <div class="px-4 text-lg text-gray-500 border-r border-gray-400 tracking-wider">
                    {{ $code }}
                </div>

                <div class="ml-4 text-lg text-gray-500 uppercase tracking-wider">
                    {{ $errorMessage }}
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
