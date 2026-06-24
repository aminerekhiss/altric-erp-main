<x-filament-panels::page>
    <x-filament-panels::form wire:submit="generateWorkflow">
        {{ $this->form }}

        <div class="flex justify-end gap-3">
            <x-filament::button type="submit" icon="heroicon-o-sparkles">
                Generate Workflow
            </x-filament::button>

            <x-filament::button type="button" color="success" icon="heroicon-o-bookmark" wire:click="saveWorkflow">
                Save Workflow
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <x-filament::section class="mt-6" heading="Workflow History">
        @if (count($history) > 0)
            <div class="space-y-2">
                @foreach ($history as $item)
                    <button
                        type="button"
                        wire:click="loadHistoryItem({{ $item['id'] }})"
                        class="w-full text-left rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-900"
                    >
                        <div class="font-medium">{{ $item['name'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $item['module'] ?: 'N/A' }} | {{ $item['created_at'] ?: '' }}
                        </div>
                    </button>
                @endforeach
            </div>
        @else
            <div class="text-sm text-gray-600 dark:text-gray-400">No saved workflows yet.</div>
        @endif
    </x-filament::section>

    @if (is_array($workflow))
        <x-filament::section class="mt-6" :heading="$workflow['title'] ?? 'Generated Workflow'">
            @if (!empty($workflow['summary']))
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">{{ $workflow['summary'] }}</p>
            @endif

            @if (!empty($workflow['steps']) && is_array($workflow['steps']))
                <ol class="space-y-3 list-decimal pl-5">
                    @foreach ($workflow['steps'] as $step)
                        <li>
                            <div class="font-medium">{{ $step['name'] ?? 'Step' }}</div>
                            @if (!empty($step['description']))
                                <div class="text-sm text-gray-700 dark:text-gray-300">{{ $step['description'] }}</div>
                            @endif
                            @if (!empty($step['owner']))
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Owner: {{ $step['owner'] }}</div>
                            @endif
                            @if (!empty($step['tools']) && is_array($step['tools']))
                                <div class="text-xs text-gray-600 dark:text-gray-400">Tools: {{ implode(', ', $step['tools']) }}</div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @elseif (!empty($workflow['raw']))
                <pre class="text-xs whitespace-pre-wrap">{{ $workflow['raw'] }}</pre>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
