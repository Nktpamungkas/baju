<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => 'required|string']);

        if ($data['password'] !== config('nale.admin_password')) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $request->session()->regenerate();
        $request->session()->put('is_admin', true);

        return redirect()->route('admin.products');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('is_admin');

        return redirect()->route('admin.login');
    }
}
