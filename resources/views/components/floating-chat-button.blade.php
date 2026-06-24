@php
    use App\Filament\Company\Resources\Sales\AccountMessageResource;

    $canViewMessages = AccountMessageResource::canViewAny();
    $canCreateMessage = AccountMessageResource::canCreate();

    if (! $canViewMessages && ! $canCreateMessage) {
        return;
    }

    $chatUrl = $canCreateMessage
        ? AccountMessageResource::getUrl('create')
        : AccountMessageResource::getUrl('index');

    $unread = AccountMessageResource::getNavigationBadge();
@endphp

<a
    href="{{ $chatUrl }}"
    class="fixed bottom-6 right-6 z-[70] inline-flex items-center gap-2 rounded-full bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-2xl transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-300"
    title="Open chat"
>
    <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
    <span>Chat</span>
    @if($unread)
        <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-danger-600 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">
            {{ $unread }}
        </span>
    @endif
</a>
