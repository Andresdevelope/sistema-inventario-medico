<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'name',
        'email',
        'password',
        'security_color_answer',
        'security_animal_answer',
        'security_padre_answer',
        'login_attempts',
        'locked_until',
        'notif_mov_last_seen_id',
        'role', // permite asignar el rol desde formularios o seeds
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'security_color_answer',
        'security_animal_answer',
        'security_padre_answer',
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
            'locked_until' => 'datetime',
        ];
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user')->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        // Admin/superadmin mantienen acceso total por política.
        if (($this->role ?? null) === 'admin') {
            return true;
        }

        if ($permission === '') {
            return false;
        }

        return $this->permissions()->where('slug', $permission)->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if (($this->role ?? null) === 'admin') {
            return true;
        }

        $permissions = array_values(array_filter($permissions));
        if (empty($permissions)) {
            return false;
        }

        return $this->permissions()->whereIn('slug', $permissions)->exists();
    }
}
