<?php

namespace App\Livewire\Forms;

use App\Models\Volume;
use App\Services\VolumeConnectionTester;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class VolumeForm extends Form
{
    public ?Volume $volume = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|in:s3,local')]
    public string $type = 'local';

    // S3 Config
    #[Validate('required_if:type,s3|string|max:255')]
    public string $bucket = '';

    #[Validate('nullable|string|max:255')]
    public string $prefix = '';

    // Local Config
    #[Validate('required_if:type,local|string|max:500')]
    public string $path = '';

    // Connection test state
    public ?string $connectionTestMessage = null;

    public bool $connectionTestSuccess = false;

    public bool $testingConnection = false;

    public function setVolume(Volume $volume): void
    {
        $this->volume = $volume;
        $this->name = $volume->name;
        $this->type = $volume->type;

        /** @var array<string, mixed> $config */
        $config = $volume->config;

        // Load config based on type
        if ($volume->type === 's3') {
            $this->bucket = $config['bucket'] ?? '';
            $this->prefix = $config['prefix'] ?? '';
        } elseif ($volume->type === 'local') {
            $this->path = $config['path'] ?? '';
        }
    }

    public function store(): void
    {
        // Validate with unique rule for new volumes
        $this->validate([
            'name' => 'required|string|max:255|unique:volumes,name',
            'type' => 'required|string|in:s3,local',
            'bucket' => 'required_if:type,s3|string|max:255',
            'prefix' => 'nullable|string|max:255',
            'path' => 'required_if:type,local|string|max:500',
        ]);

        $config = $this->buildConfig();

        Volume::create([
            'name' => $this->name,
            'type' => $this->type,
            'config' => $config,
        ]);
    }

    public function update(): void
    {
        // Add unique validation for name, ignoring current volume
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:volumes,name,'.$this->volume->id],
            'type' => 'required|string|in:s3,local',
            'bucket' => 'required_if:type,s3|string|max:255',
            'prefix' => 'nullable|string|max:255',
            'path' => 'required_if:type,local|string|max:500',
        ]);

        $config = $this->buildConfig();

        $this->volume->update([
            'name' => $this->name,
            'type' => $this->type,
            'config' => $config,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function buildConfig(): array
    {
        return match ($this->type) {
            's3' => [
                'bucket' => $this->bucket,
                'prefix' => $this->prefix ?? '',
            ],
            'local' => [
                'path' => $this->path,
            ],
            default => throw new \InvalidArgumentException("Invalid volume type: {$this->type}"),
        };
    }

    public function testConnection(): void
    {
        $this->testingConnection = true;
        $this->connectionTestMessage = null;

        // Validate type-specific fields
        try {
            if ($this->type === 'local') {
                $this->validate([
                    'path' => 'required|string|max:500',
                ]);
            } elseif ($this->type === 's3') {
                $this->validate([
                    'bucket' => 'required|string|max:255',
                ]);
            }
        } catch (ValidationException $e) {
            $this->testingConnection = false;
            $this->connectionTestSuccess = false;
            $this->connectionTestMessage = 'Please fill in all required configuration fields.';

            return;
        }

        /** @var VolumeConnectionTester $tester */
        $tester = app(VolumeConnectionTester::class);

        // Create a temporary Volume model for testing (not persisted)
        $testVolume = new Volume([
            'name' => $this->name ?: 'test-volume',
            'type' => $this->type,
            'config' => $this->buildConfig(),
        ]);

        $result = $tester->test($testVolume);

        $this->connectionTestSuccess = $result['success'];
        $this->connectionTestMessage = $result['message'];
        $this->testingConnection = false;
    }
}
