<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\DemoBackupService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private readonly DemoBackupService $demoBackupService
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $isFirstUser = User::count() === 0;
        $createDemoBackup = $isFirstUser && ! empty($input['create_demo_backup']);

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $isFirstUser ? User::ROLE_ADMIN : User::ROLE_MEMBER,
            'invitation_accepted_at' => now(),
        ]);

        if ($createDemoBackup) {
            try {
                $this->demoBackupService->createDemoBackup();
            } catch (\Throwable $e) {
                // Log the error but don't fail registration
                Log::warning('Failed to create demo backup during registration', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $user;
    }
}
