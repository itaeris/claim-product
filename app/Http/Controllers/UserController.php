<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->present($user))
            ->values()
            ->all();

        return response()->json($users);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = User::query()->create($this->attributes($request));

        return response()->json($this->present($user), 201);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $this->guardLastAdmin($user, $request->string('role')->toString() === 'admin');
        $user->update($this->attributes($request));

        return response()->json($this->present($user));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()?->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Kamu tidak bisa menghapus akun sendiri.',
            ]);
        }

        $this->guardLastAdmin($user, false);

        if ($user->claims()->exists()) {
            throw ValidationException::withMessages([
                'user' => 'User masih memiliki klaim.',
            ]);
        }

        $user->delete();

        return response()->json(['message' => 'User dihapus.']);
    }

    private function guardLastAdmin(User $user, bool $willBeAdmin): void
    {
        if ($user->is_admin && ! $willBeAdmin && ! User::query()->where('is_admin', true)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Harus ada minimal satu admin.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(UserRequest $request): array
    {
        $name = $request->string('name')->toString();
        $attributes = [
            'name' => $name,
            'email' => $request->string('email')->toString(),
            'team' => $request->string('team')->toString(),
            'employment_type' => $request->string('employment_type')->toString(),
            'company' => $request->string('company')->toString(),
            'initials' => User::initialsFrom($name),
            'is_admin' => $request->string('role')->toString() === 'admin',
        ];

        if ($request->filled('password')) {
            $attributes['password'] = $request->string('password')->toString();
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'team' => $user->team,
            'initials' => $user->initials,
            'employment_type' => $user->employment_type,
            'employment_label' => $user->employmentLabel(),
            'company' => $user->company,
            'role' => $user->is_admin ? 'admin' : 'user',
        ];
    }
}
