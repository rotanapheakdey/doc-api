<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['avatar_url']; // Add this to automatically include avatar URL in JSON responses

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            // For Sail, URL will be like: http://localhost/storage/avatars/filename.jpg
            return Storage::url($this->avatar);
        }

        // Generate default avatar with user's initials
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=random&size=100&bold=true";
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function canHaveDepartment()
    {
        return !in_array($this->role, ['dg', 'file_dept']);
    }

    public function canManageUsers()
    {
        return in_array($this->role, ['dg', 'file_dept']);
    }
}
