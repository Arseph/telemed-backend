<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable; 
class Meeting extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'teleconsults';
    protected $guarded = array();

    public function patient() {
    	return $this->hasOne(Patient::class, 'id', 'patient_id');
    }

    public function doctor() {
        return $this->hasOne(User::class, 'id', 'doctor_id');
    }

    public function pendmeet() {
        return $this->hasOne(PendingMeeting::class, 'meet_id', 'id');
    }

    public function encoded() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function docorder() {
        return $this->hasOne(DoctorOrder::class, 'meet_id', 'id');
    }
    public function clinical() {
        return $this->hasOne(ClinicalHistory::class, 'meeting_id', 'id');
    }
    public function covidassess() {
        return $this->hasOne(CovidAssessment::class, 'meeting_id', 'id');
    }
    public function covidscreen() {
        return $this->hasOne(CovidScreening::class, 'meeting_id', 'id');
    }
    public function diagassess() {
        return $this->hasOne(DiagnosisAssessment::class, 'meeting_id', 'id');
    }
    public function planmanage() {
        return $this->hasOne(PlanManagement::class, 'meeting_id', 'id');
    }
    public function demoprof() {
        return $this->hasOne(DemoProfile::class, 'meeting_id', 'id');
    }
    public function phyexam() {
        return $this->hasOne(PhysicalExam::class, 'meeting_id', 'id');
    }
}
