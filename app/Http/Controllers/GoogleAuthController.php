<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $target = $request->query('redirect');

        if (is_string($target) && $this->safePath($target)) {
            $request->session()->put('url.intended', $target);
        }

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect('/login?google=failed');
        }

        $email = strtolower((string) $google->getEmail());
        $googleId = (string) $google->getId();

        if ($email === '' || ! $this->domainAllowed($email)) {
            return redirect('/login?google=domain');
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user === null) {
            return redirect('/login?google=unregistered');
        }

        $taken = User::query()->where('google_id', $googleId)->whereKeyNot($user->id)->exists();

        if ($taken || ($user->google_id !== null && $user->google_id !== $googleId)) {
            return redirect('/login?google=mismatch');
        }

        if ($user->google_id === null) {
            $user->google_id = $googleId;
        }

        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }

        if ($user->isDirty()) {
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->intendedPath($request));
    }

    private function domainAllowed(string $email): bool
    {
        $allowed = array_values(array_filter(array_map(
            fn (string $domain) => strtolower(ltrim(trim($domain), '@')),
            explode(',', (string) config('services.google.allowed_domains')),
        )));

        if ($allowed === []) {
            return true;
        }

        return in_array(strtolower((string) str($email)->after('@')), $allowed, true);
    }

    private function intendedPath(Request $request): string
    {
        $target = $request->session()->pull('url.intended', '/');

        return is_string($target) && $this->safePath($target) ? $target : '/';
    }

    private function safePath(string $path): bool
    {
        return str_starts_with($path, '/') && ! str_starts_with($path, '//');
    }
}
