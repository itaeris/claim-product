<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => $this->userPayload($request->user()),
            'csrf' => csrf_token(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'csrf' => csrf_token(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(?User $user): array
    {
        return [
            'name' => $user?->name,
            'team' => $user?->team,
            'initials' => $user?->initials,
            'email' => $user?->email,
            'role' => $user?->is_admin ? 'admin' : 'user',
            'is_admin' => (bool) $user?->is_admin,
        ];
    }
}
