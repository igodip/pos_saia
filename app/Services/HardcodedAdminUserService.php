<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

class HardcodedAdminUserService
{
    public function username(): string
    {
        return (string) config('backend_auth.username');
    }

    public function password(): string
    {
        return (string) config('backend_auth.password');
    }

    public function matches(string $username, string $password): bool
    {
        return hash_equals($this->username(), $username)
            && hash_equals($this->password(), $password);
    }

    public function ensureAdminUser(): User
    {
        return User::query()->updateOrCreate(
            ['email' => config('backend_auth.email')],
            [
                'name' => config('backend_auth.name'),
                'password' => $this->password(),
                'role' => UserRole::from((string) config('backend_auth.role')),
                'email_verified_at' => now(),
            ],
        );
    }
}
