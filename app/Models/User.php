<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'team', 'initials', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public static function initialsFrom(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $letters = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return mb_substr($letters !== '' ? $letters : 'U', 0, 3);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
