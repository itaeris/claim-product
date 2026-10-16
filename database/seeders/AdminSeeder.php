<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrNew([
            'email' => 'dwiki@aerisbeaute.com',
        ]);

        $user->name = 'Dwiki';
        $user->team = 'Admin';
        $user->initials = 'DW';
        $user->employment_type = 'tetap';
        $user->company = 'PT Citra Pasifik Kosmetik';
        $user->is_admin = true;
        $user->email_verified_at ??= now();

        if (! $user->exists) {
            $user->password = 'aerisbeaute';
        }

        $user->save();
    }
}
