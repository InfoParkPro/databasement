<?php

namespace App\Http\Resources;

use App\Models\Snapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Snapshot
 */
class SnapshotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dbMetadata = $this->getDatabaseServerMetadata();

        return [
            'id' => $this->id,
            'database_name' => $this->database_name,
            'database_type' => $this->database_type,
            'metadata' => $this->metadata,
            'storage_uri' => $this->storage_uri,
            'file_size' => $this->file_size,
            'file_size_human' => $this->getHumanFileSize(),
            'checksum' => $this->checksum,
            'compression_type' => $this->compression_type,
            'method' => $this->method,
            'started_at' => $this->started_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'server' => $this->whenLoaded('databaseServer', fn () => [
                'id' => $this->databaseServer->id,
                'name' => $this->databaseServer->name,
            ]),
            'volume' => $this->whenLoaded('volume', fn () => [
                'id' => $this->volume->id,
                'name' => $this->volume->name,
                'type' => $this->volume->type,
            ]),
            'triggered_by' => $this->whenLoaded('triggeredBy', fn () => $this->triggeredBy ? [
                'id' => $this->triggeredBy->id,
                'name' => $this->triggeredBy->name,
            ] : null),
            'job' => $this->whenLoaded('job', fn () => [
                'id' => $this->job->id,
                'status' => $this->job->status,
                'duration' => $this->job->getHumanDuration(),
            ]),
        ];
    }
}
