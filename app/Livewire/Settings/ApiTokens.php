<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Mary\Traits\Toast;

class ApiTokens extends Component
{
    use Toast;

    public string $tokenName = '';

    public bool $showCreateModal = false;

    public bool $showTokenModal = false;

    #[Locked]
    public ?string $newToken = null;

    #[Locked]
    public ?string $deleteTokenId = null;

    public bool $showDeleteModal = false;

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->tokenName = '';
    }

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
        ]);

        $token = Auth::user()->createToken($this->tokenName);

        $this->newToken = $token->plainTextToken;
        $this->tokenName = '';
        $this->showCreateModal = false;
        $this->showTokenModal = true;
    }

    public function closeTokenModal(): void
    {
        $this->newToken = null;
        $this->showTokenModal = false;
    }

    public function confirmDelete(string $id): void
    {
        $this->deleteTokenId = $id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->deleteTokenId = null;
        $this->showDeleteModal = false;
    }

    public function deleteToken(): void
    {
        if (! $this->deleteTokenId) {
            return;
        }

        Auth::user()->tokens()->where('id', $this->deleteTokenId)->delete();

        $this->deleteTokenId = null;
        $this->showDeleteModal = false;
        $this->success(__('API token revoked successfully.'), position: 'toast-bottom');
    }

    public function render()
    {
        $tokens = Auth::user()->tokens()->latest()->get();

        return view('livewire.settings.api-tokens', [
            'tokens' => $tokens,
        ])->layout('components.layouts.app', ['title' => __('API Tokens')]);
    }
}
