<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const ROLE_USER = 'user';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_ADMIN = 'admin';
    const ROLES = [self::ROLE_USER, self::ROLE_MODERATOR, self::ROLE_ADMIN];
    const DEFAULT_ROLE = self::ROLE_USER;

    const PER_PAGE = 30;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'password_refresh_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getId() {
        return $this->id;
    }

    public function getRole(): string
    {
        if(in_array($this->role ?? null, self::ROLES)){
            return $this->role;
        }
        return self::DEFAULT_ROLE;
    }

    public function setRole(string $role): void
    {
        if(!in_array($role, self::ROLES)){
            return;
        }
        $this->role = $role;
    }

    public static function getRoleOrDefault(string $role): string
    {
        if(!in_array($role, self::ROLES)){
            return self::DEFAULT_ROLE;
        }
        return $role;
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? null) === self::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return ($this->role ?? null) === self::ROLE_MODERATOR;
    }

    public function isAnyManager(): bool
    {
        return in_array($this->role ?? null, [self::ROLE_MODERATOR, self::ROLE_ADMIN]);
    }

    public function isPasswordRefreshed(): bool
    {
        return !!$this->password_refresh_date;
    }

    public function needToRefreshPassword(): bool
    {
        return !$this->isPasswordRefreshed();
    }
}
