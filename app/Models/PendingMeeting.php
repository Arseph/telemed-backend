<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class PendingMeeting extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pending_meetings';
    protected $guarded = array();

    public function patient() {
        return $this->hasOne(Patient::class, 'id', 'patient_id');
    }

    public function doctor() {
        return $this->hasOne(User::class, 'id', 'doctor_id');
    }
    public function facility() {
        return $this->hasOne(Facility::class, 'id', 'facility_id');
    }

    public function encoded() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function telecategory() {
        return $this->hasOne(DocCategory::class, 'id', 'tele_cate_id');
    }
    public function meetingone() {
        return $this->hasOne(Meeting::class, 'id', 'meet_id');
    }
}
