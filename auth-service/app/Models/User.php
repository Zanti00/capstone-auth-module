<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'is_active'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Laravel\Sanctum\HasApiTokens;

    public function credentials()
    {
        return $this->hasOne(UserCredential::class);
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
        ];
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function scopeFilterByRole($query, $roleId)
    {
        return $query->whereHas('profile', function ($q) use ($roleId) {
            $q->where('role_id', $roleId);
        });
    }

    public function scopeFilterByDepartment($query, $departmentId)
    {
        return $query->whereHas('profile', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    public function scopeFilterByStatus($query, $isActive)
    {
        return $query->where('is_active', $isActive);
    }
}
