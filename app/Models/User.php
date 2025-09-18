<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Facility;
class User extends Authenticatable implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use Notifiable,HasApiTokens,HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'doc_cat_id',
        'doctor_id',
        'facility_id',
        'username',
        'password',
        'level',
        'fname',
        'mname',
        'lname',
        'title',
        'contact',
        'email',
        'accrediation_no',
        'accrediation_validity',
        'license_no',
        'prefix',
        'picture',
        'designation',
        'status',
        'last_login',
        'login_status',
        'void'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function facility() {
        return $this->hasOne(Facility::class, 'id', 'facility_id');
    }
    public function patient() {
        return $this->hasOne(Patient::class, 'account_id', 'id');
    }
    public function reqpatient() {
        return $this->hasMany(Patient::class, 'doctor_id', 'id');
    }
    public function pendmeet() {
        return $this->hasMany(PendingMeeting::class, 'doctor_id', 'id');
    }
    public function zoom() {
        return $this->hasOne(ZoomCredential::class, 'doctor_id', 'id');
    }
}
