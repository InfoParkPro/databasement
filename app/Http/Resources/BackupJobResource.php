<?php

namespace App\Http\Resources;

use App\Models\BackupJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BackupJob
 */
class BackupJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'duration' => $this->getHumanDuration(),
            'error_message' => $this->error_message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'type' => $this->whenLoaded('snapshot', fn () => 'backup', fn () => $this->whenLoaded('restore', fn () => 'restore', null)),
            'snapshot' => $this->whenLoaded('snapshot', fn () => [
                'id' => $this->snapshot->id,
                'metadata' => $this->snapshot->metadata,
                'database_name' => $this->snapshot->database_name,
                'database_type' => $this->snapshot->database_type,
                'server' => $this->whenLoaded('snapshot', fn () => $this->snapshot->relationLoaded('databaseServer') ? [
                    'id' => $this->snapshot->databaseServer->id,
                    'name' => $this->snapshot->databaseServer->name,
                ] : null),
                'triggered_by' => $this->whenLoaded('snapshot', fn () => $this->snapshot->relationLoaded('triggeredBy') && $this->snapshot->triggeredBy ? [
                    'id' => $this->snapshot->triggeredBy->id,
                    'name' => $this->snapshot->triggeredBy->name,
                ] : null),
            ]),
            'restore' => $this->whenLoaded('restore', fn () => [
                'id' => $this->restore->id,
                'schema_name' => $this->restore->schema_name,
                'target_server' => $this->whenLoaded('restore', fn () => $this->restore->relationLoaded('targetServer') ? [
                    'id' => $this->restore->targetServer->id,
                    'name' => $this->restore->targetServer->name,
                ] : null),
                'triggered_by' => $this->whenLoaded('restore', fn () => $this->restore->relationLoaded('triggeredBy') && $this->restore->triggeredBy ? [
                    'id' => $this->restore->triggeredBy->id,
                    'name' => $this->restore->triggeredBy->name,
                ] : null),
            ]),
        ];
    }
}
