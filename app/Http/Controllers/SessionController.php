<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function show(Request $request): JsonResponse|Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->noContent();
        }

        return response()->json($this->userPayload($user));
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

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah.',
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
            'phone' => $user?->phone,
            'employment_type' => $user?->employment_type,
            'employment_label' => $user?->employmentLabel(),
            'company' => $user?->company,
            'role' => $user?->is_admin ? 'admin' : 'user',
            'is_admin' => (bool) $user?->is_admin,
        ];
    }
}
