<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\HardcodedAdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly HardcodedAdminUserService $adminUserService)
    {
    }

    public function store(LoginRequest $request): JsonResponse
    {
        if (! $this->adminUserService->matches(
            $request->string('username')->value(),
            $request->string('password')->value(),
        )) {
            throw ValidationException::withMessages([
                'username' => 'Invalid credentials.',
            ]);
        }

        $user = $this->adminUserService->ensureAdminUser();

        return response()->json([
            'token' => $user->createToken($request->input('device_name', 'api'))->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $this->adminUserService->username(),
                'role' => $user->role->value,
            ],
        ]);
    }

    public function destroy(): JsonResponse
    {
        request()->user()->currentAccessToken()?->delete();

        return response()->json(status: 204);
    }
}
