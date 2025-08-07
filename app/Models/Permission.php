<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    const BASIC = 1;
    const INTERMEDIATE = 2;
    const ADVANCED = 3;
    const PLAN_BASIC = 5;
    const PLAN_INTERMEDIATE = 25;
    const PLAN_ADVANCED = 100;

    public function users()
    {
        return $this->belongsToMany(User::class, 'permission_service_user')
            ->withPivot('service_id')
            ->withTimestamps();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'permission_service_user')
            ->withPivot('user_id')
            ->withTimestamps();
    }
}
