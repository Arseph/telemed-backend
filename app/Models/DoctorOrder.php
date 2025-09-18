<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class DoctorOrder extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'doctor_order';
    protected $guarded = array();

    public function encoded() {
        return $this->hasOne(User::class, 'id', 'encodedby');
    }
    public function modified() {
        return $this->hasOne(User::class, 'id', 'modifyby');
    }
    public function patient() {
        return $this->hasOne(Patient::class, 'id', 'patientid');
    }
    public function labreq() {
        return $this->hasMany(DocOrderLabReq::class, 'docorderid', 'id');
    }
}
