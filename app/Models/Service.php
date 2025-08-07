<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    const XMl = 1;

    public function users()
    {
        return $this->belongsToMany(User::class, 'permission_service_user')
            ->withPivot('permission_id')
            ->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_service_user')
            ->withPivot('user_id')
            ->withTimestamps();
    }

}
