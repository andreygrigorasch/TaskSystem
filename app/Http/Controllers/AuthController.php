<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Неверная почта или пароль.']);
        }
        $request->session()->regenerate();

        return response()->json(['data' => $request->user()->only('id', 'name', 'email', 'role')]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->only('id', 'name', 'email', 'role')]);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
