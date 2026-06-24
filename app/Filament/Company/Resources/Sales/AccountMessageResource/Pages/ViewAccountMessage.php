<?php

namespace App\Filament\Company\Resources\Sales\AccountMessageResource\Pages;

use App\Filament\Company\Resources\Sales\AccountMessageResource;
use App\Models\Common\AccountMessage;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewAccountMessage extends ViewRecord
{
    protected static string $resource = AccountMessageResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        /** @var AccountMessage $message */
        $message = $this->record;

        abort_unless(AccountMessageResource::canAccessRecord($message), 404);

        if ((int) $message->recipient_user_id === (int) auth()->id() && $message->read_at === null) {
            $message->markAsRead();
            $message->refresh();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_read')
                ->label('Mark read')
                ->icon('heroicon-m-check')
                ->visible(function (AccountMessage $record) {
                    return (int) $record->recipient_user_id === (int) auth()->id() && $record->read_at === null;
                })
                ->action(function (AccountMessage $record) {
                    abort_unless((int) $record->recipient_user_id === (int) auth()->id(), 404);

                    $record->markAsRead();
                    $this->refreshFormData(['read_at']);
                }),
            Actions\Action::make('new_message')
                ->label('New Message')
                ->icon('heroicon-m-paper-airplane')
                ->url(AccountMessageResource::getUrl('create')),
            Actions\Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-m-arrow-uturn-left')
                ->url(function (AccountMessage $record) {
                    $subject = str_starts_with($record->subject, 'Re: ') ? $record->subject : 'Re: ' . $record->subject;
                    $quotedBody = "\n\n---\n" . 'On ' . $record->created_at?->toDateTimeString() . ', ' . ($record->sender?->name ?? 'Unknown') . ' wrote:' . "\n" . $record->message;

                    return AccountMessageResource::getUrl('create', [
                        'to' => $record->sender_user_id,
                        'subject' => $subject,
                        'body' => $quotedBody,
                    ]);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Message')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('sender.name')
                            ->label('From'),
                        TextEntry::make('recipient.name')
                            ->label('To'),
                        TextEntry::make('subject')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Sent at')
                            ->dateTime(),
                        TextEntry::make('read_at')
                            ->label('Read at')
                            ->dateTime()
                            ->placeholder('Unread'),
                        TextEntry::make('message')
                            ->label('Body')
                            ->columnSpanFull(),
                        TextEntry::make('attachment_path')
                            ->label('Attachment')
                            ->formatStateUsing(fn (?string $state) => $state ? basename($state) : 'No attachment')
                            ->url(fn (AccountMessage $record) => $record->attachment_path ? Storage::url($record->attachment_path) : null)
                            ->openUrlInNewTab(),
                    ]),
            ]);
    }
}
