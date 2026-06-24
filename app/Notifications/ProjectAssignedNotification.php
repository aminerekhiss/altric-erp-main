<?php

namespace App\Notifications;

use App\Filament\Company\Resources\Sales\ProjectResource;
use App\Models\Common\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Project $project) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New project assignment',
            'body' => 'You have been assigned to project: ' . $this->project->name,
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'url' => ProjectResource::getUrl('edit', ['record' => $this->project]),
        ];
    }
}
