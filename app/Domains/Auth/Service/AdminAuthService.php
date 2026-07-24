<?php

namespace App\Domains\Auth\Service;

use Illuminate\Http\Request;

class AdminAuthService
{
    public function attempt(string $password): bool
    {
        return $password === config('nale.admin_password');
    }

    public function login(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->put('is_admin', true);
    }

    public function logout(Request $request): void
    {
        $request->session()->forget('is_admin');
    }

    public function check(Request $request): bool
    {
        return (bool) $request->session()->get('is_admin');
    }
}
