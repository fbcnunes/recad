<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    public function adminGrant(): HasOne
    {
        return $this->hasOne(AdminUser::class);
    }

    public static function configuredInitialAdminUsernames(): array
    {
        $configured = config('recad.initial_admin_usernames', []);
        if (!is_array($configured)) {
            return [];
        }

        $toLower = static fn (string $v): string => function_exists('mb_strtolower')
            ? mb_strtolower($v, 'UTF-8')
            : strtolower($v);

        return array_values(array_unique(array_filter(array_map(
            fn ($username): string => $toLower(trim((string) $username)),
            $configured
        ))));
    }

    public function isConfiguredInitialAdmin(): bool
    {
        $usernameRaw = (string) $this->username;
        $username = function_exists('mb_strtolower')
            ? mb_strtolower($usernameRaw, 'UTF-8')
            : strtolower($usernameRaw);
        if ($username === '') {
            return false;
        }

        return in_array($username, self::configuredInitialAdminUsernames(), true);
    }

    public function isManualAdmin(): bool
    {
        if ($this->relationLoaded('adminGrant')) {
            return $this->adminGrant !== null;
        }

        return $this->adminGrant()->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->isConfiguredInitialAdmin() || $this->isManualAdmin();
    }
}
