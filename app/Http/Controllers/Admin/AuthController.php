<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HardcodedAdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly HardcodedAdminUserService $adminUserService)
    {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin_authenticated', false)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! $this->adminUserService->matches($credentials['username'], $credentials['password'])) {
            return back()
                ->withErrors(['username' => 'Credenziali non valide.'])
                ->onlyInput('username');
        }

        $user = $this->adminUserService->ensureAdminUser();

        $request->session()->put([
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
        ]);

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_authenticated', 'admin_user_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
