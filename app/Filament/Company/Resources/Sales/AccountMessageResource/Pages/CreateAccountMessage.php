<?php

namespace App\Filament\Company\Resources\Sales\AccountMessageResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\AccountMessageResource;
use App\Models\Common\AccountMessage;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

class CreateAccountMessage extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = AccountMessageResource::class;

    #[Url(as: 'to')]
    public ?int $replyToUserId = null;

    #[Url(as: 'subject')]
    public ?string $replySubject = null;

    #[Url(as: 'body')]
    public ?string $replyBody = null;

    public function mount(): void
    {
        parent::mount();

        $data = [];

        if ($this->replyToUserId) {
            $data['recipient_user_id'] = $this->replyToUserId;
        }

        if ($this->replySubject) {
            $data['subject'] = $this->replySubject;
        }

        if ($this->replyBody) {
            $data['message'] = $this->replyBody;
        }

        if (! empty($data)) {
            $this->form->fill($data);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $recipientUserId = (int) ($data['recipient_user_id'] ?? 0);

        AccountMessageResource::validateRecipientOrFail($recipientUserId);

        $data['sender_user_id'] = auth()->id();
        $data['company_id'] = auth()->user()?->current_company_id;

        /** @var AccountMessage $message */
        $message = parent::handleRecordCreation($data);

        AccountMessageResource::notifyRecipient($message);

        return $message;
    }
}
