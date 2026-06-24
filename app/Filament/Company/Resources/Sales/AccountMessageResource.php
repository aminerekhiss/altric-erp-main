<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\AccountMessageResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\AccountMessage;
use App\Models\User;
use App\Notifications\AccountMessageReceivedNotification;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AccountMessageResource extends Resource
{
    protected static ?string $model = AccountMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $modelLabel = 'Message';

    protected static ?string $pluralModelLabel = 'Messages';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $userId = auth()->id();

        if (! $userId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(static function (Builder $innerQuery) use ($userId) {
            $innerQuery
                ->where('sender_user_id', $userId)
                ->orWhere('recipient_user_id', $userId);
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        $count = AccountMessage::query()
            ->where('recipient_user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('New Message')
                    ->schema([
                        Forms\Components\Select::make('recipient_user_id')
                            ->label('To')
                            ->options(static::getRecipientOptions())
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->rows(8)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Attachment')
                            ->directory('messages/attachments')
                            ->openable()
                            ->downloadable()
                            ->maxSize(10240)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/png',
                                'image/jpeg',
                                'image/webp',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/plain',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $userId = auth()->id();

                return $query
                    ->where(function (Builder $innerQuery) use ($userId) {
                        $innerQuery
                            ->where('sender_user_id', $userId)
                            ->orWhere('recipient_user_id', $userId);
                    })
                    ->latest();
            })
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('From')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recipient.name')
                    ->label('To')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('direction')
                    ->label('Direction')
                    ->state(function (AccountMessage $record) {
                        return (int) $record->sender_user_id === (int) auth()->id() ? 'Outgoing' : 'Incoming';
                    })
                    ->badge()
                    ->color(fn (string $state) => $state === 'Incoming' ? 'info' : 'gray'),
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Read')
                    ->boolean()
                    ->state(function (AccountMessage $record) {
                        if ((int) $record->sender_user_id === (int) auth()->id()) {
                            return true;
                        }

                        return $record->read_at !== null;
                    }),
                Tables\Columns\IconColumn::make('attachment_path')
                    ->label('Attachment')
                    ->boolean()
                    ->state(fn (AccountMessage $record) => filled($record->attachment_path)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('box')
                    ->label('Mailbox')
                    ->options([
                        'inbox' => 'Inbox',
                        'sent' => 'Sent',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        $userId = auth()->id();

                        return match ($value) {
                            'inbox' => $query->where('recipient_user_id', $userId),
                            'sent' => $query->where('sender_user_id', $userId),
                            default => $query,
                        };
                    }),
                Tables\Filters\TernaryFilter::make('is_unread')
                    ->label('Unread')
                    ->queries(
                        true: fn (Builder $query) => $query->where('recipient_user_id', auth()->id())->whereNull('read_at'),
                        false: fn (Builder $query) => $query->where('recipient_user_id', auth()->id())->whereNotNull('read_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_read')
                    ->label('Mark read')
                    ->icon('heroicon-m-check')
                    ->visible(fn (AccountMessage $record) => (int) $record->recipient_user_id === (int) auth()->id() && $record->read_at === null)
                    ->action(function (AccountMessage $record) {
                        $record->markAsRead();
                    }),
                Tables\Actions\ViewAction::make()
                    ->url(fn (AccountMessage $record) => Pages\ViewAccountMessage::getUrl(['record' => $record])),
                Tables\Actions\Action::make('open_attachment')
                    ->label('Open attachment')
                    ->icon('heroicon-m-paper-clip')
                    ->visible(fn (AccountMessage $record) => filled($record->attachment_path))
                    ->url(fn (AccountMessage $record) => $record->attachment_path ? Storage::url($record->attachment_path) : null)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountMessages::route('/'),
            'create' => Pages\CreateAccountMessage::route('/create'),
            'view' => Pages\ViewAccountMessage::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canAccessFor('read');
    }

    public static function canCreate(): bool
    {
        return static::canAccessFor('create');
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return static::canAccessRecord($record);
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_MESSAGES, $user, $company)) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, $permission);
    }

    public static function getRecipientOptions(): array
    {
        $query = static::getRecipientsBaseQuery();

        if (! $query) {
            return [];
        }

        return $query
            ->orderBy('users.name')
            ->pluck('users.name', 'users.id')
            ->toArray();
    }

    public static function getAllowedRecipientIds(): array
    {
        $query = static::getRecipientsBaseQuery();

        if (! $query) {
            return [];
        }

        return $query->pluck('users.id')->map(fn ($id) => (int) $id)->toArray();
    }

    protected static function getRecipientsBaseQuery(): ?BelongsToMany
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return null;
        }

        $senderRoleKey = $user->ownsCompany($company) ? 'owner' : ($user->companyRole($company)?->key ?? null);

        $query = $company->users()
            ->where('users.id', '!=', $user->id);

        if ($senderRoleKey === 'company') {
            $query->wherePivot('role', 'employee');
        } elseif ($senderRoleKey === 'employee') {
            $query->wherePivot('role', 'company');
        } else {
            $query->wherePivotIn('role', ['company', 'employee']);
        }

        return $query;
    }

    public static function validateRecipientOrFail(int $recipientUserId): void
    {
        if (! in_array($recipientUserId, static::getAllowedRecipientIds(), true)) {
            throw ValidationException::withMessages([
                'data.recipient_user_id' => 'Recipient is not allowed. Company accounts can message employees, and employees can message company accounts.',
            ]);
        }
    }

    public static function notifyRecipient(AccountMessage $message): void
    {
        $recipient = $message->recipient;

        if (! $recipient) {
            return;
        }

        $recipient->notify(new AccountMessageReceivedNotification($message));
    }

    public static function canAccessRecord(EloquentModel $record): bool
    {
        $userId = auth()->id();

        if (! $userId || ! $record instanceof AccountMessage) {
            return false;
        }

        return (int) $record->sender_user_id === (int) $userId
            || (int) $record->recipient_user_id === (int) $userId;
    }
}
