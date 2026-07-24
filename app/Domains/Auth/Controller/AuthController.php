<?php

namespace App\Domains\Auth\Controller;

use App\Domains\Auth\Service\AdminAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthController
{
    public function __construct(private AdminAuthService $auth)
    {
    }

    public function showLogin(): Response
    {
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => 'required|string']);

        if (! $this->auth->attempt($data['password'])) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $this->auth->login($request);

        return redirect()->route('admin.products');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout($request);

        return redirect()->route('admin.login');
    }
}
