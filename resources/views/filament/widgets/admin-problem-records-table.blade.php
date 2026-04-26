<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Проблемні записи
        </x-slot>

        <x-slot name="description">
            Ліди, клієнти або поліси без активного менеджера.
        </x-slot>

        @if ($records->isEmpty())
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Проблемних записів не знайдено.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4 font-medium">Тип</th>
                            <th class="py-2 pr-4 font-medium">Запис</th>
                            <th class="py-2 pr-4 font-medium">Проблема</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-4">{{ $record['type'] }}</td>
                                <td class="py-2 pr-4">{{ $record['label'] }}</td>
                                <td class="py-2 pr-4 text-danger-600 dark:text-danger-400">
                                    {{ $record['problem'] }}
                                </td>
                                <td class="py-2 pr-4 text-right">
                                    <a href="{{ $record['url'] }}" class="text-primary-600 hover:underline">
                                        Відкрити
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>