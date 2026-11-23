<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    /**
     * Cek apakah user adalah administrator
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user adalah team leader
     */
    public function isTeamLeader(): bool
    {
        return $this->role === 'teamleader';
    }

    /**
     * Hak akses cetak BSTB & cetak rekap
     */
    public function canPrintBstb(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Hak akses manajemen batch
     */
    public function canManageBatches(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Hak akses standar manufaktur & mesin
     */
    public function canManageMaster(): bool
    {
        return $this->isAdmin();
    }
}
