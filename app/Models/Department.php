<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function vdgs(): HasMany
    {
        return $this->hasMany(User::class, 'department_id')->where('role', 'vdg');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'department_id')->where('role', 'staff');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'assigned_department_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
