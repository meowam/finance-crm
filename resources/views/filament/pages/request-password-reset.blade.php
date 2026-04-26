<x-filament-panels::page>
    <div class="max-w-2xl">
        <x-filament::section>
            <x-slot name="heading">
                Запит на зміну пароля
            </x-slot>

            <div class="space-y-5 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Оскільки система не використовує реальну поштову інтеграцію, пароль змінює адміністратор вручну.
                    Після створення запиту адміністратор побачить його на системній панелі.
                </p>

                @if ($hasPendingRequest)
                    <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 text-warning-800 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-200" style="margin-top: 40px;font-weight: 600;">
                        У вас уже є активний запит на зміну пароля.
                    </div>
                @else
                    <div style="margin-top: 40px;">
                        <x-filament::button wire:click="createRequest" icon="heroicon-o-key" >
                            Створити запит
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>