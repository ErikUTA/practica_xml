<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'micro_site_profile_id',
        'user_type_id',
        'record_type_id',
        'active'
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
    //default
    protected $attributes = [
        'active' => 1
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function accessPointsUsers(){
        return $this->hasMany(AccessPointUser::class,'user_id');
    }

    public function access_point(){
        return $this->belongsToMany(AccessPoint::class,'access_point_user','user_id','access_point_id');
    }

    public function role() {
        return $this->belongsTo(Role::class,'role_id');
    }

    public function microSiteProfile() {
        return $this->belongsTo(MicroSiteProfile::class,'micro_site_profile_id');
    }

    public function userType() {
        return $this->belongsTo(UserType::class,'user_type_id');
    }

    public function recordType() {
        return $this->belongsTo(RecordType::class,'record_type_id');
    }

    public function person() {
        return $this->hasOne(Person::class,'user_id');
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'permission_service_user')
            ->withPivot('permission_id')
            ->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_service_user')
            ->withPivot('service_id')
            ->withTimestamps();
    }
}
