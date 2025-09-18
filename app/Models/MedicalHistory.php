<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable; 
class MedicalHistory extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'medical_histories';
    protected $guarded = array();

    public function patient() {
    	return $this->hasOne(Patient::class, 'id', 'patient_id');
    }

    public function icd() {
    	return $this->hasOne(Diagnosis::class, 'id', 'icd10');
    }
}
