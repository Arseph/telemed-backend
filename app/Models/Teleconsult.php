<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Support\Facades\DB;

class Teleconsult extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'teleconsults';
    protected $guarded = array();

    // public function patient() {
    // 	return $this->hasOne(Patient::class, 'id', 'patient_id');
    // }

    public function patient() {
    	return $this->hasOne(PatientV2::class, 'id', 'patient_id');
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

     public function getPatientAddressAttribute()
    {
        if (!$this->patient) return null;

        $pat = $this->patient;

        // You can cast varchar→int for joins if needed
        $address = DB::table('provinces as provp')
            ->leftJoin('municipal_cities as munp', 'munp.muni_psgc', '=', DB::raw('CAST('.$pat->citycode.' AS UNSIGNED)'))
            ->leftJoin('barangays as brgyp', 'brgyp.brg_psgc', '=', DB::raw('CAST('.$pat->bgycode.' AS UNSIGNED)'))
            ->where('provp.prov_psgc', DB::raw('CAST('.$pat->provcode.' AS UNSIGNED)'))
            ->select(
                'provp.prov_name as province',
                'munp.muni_name as city',
                'brgyp.brg_name as barangay',
                DB::raw("CONCAT_WS(', ', brgyp.brg_name, munp.muni_name, provp.prov_name) as full_address")
            )
            ->first();

        return $address ? $address->full_address : null;
    }

    public function getDoctorAddressAttribute()
    {
        if (!$this->doctor) return null;

        $facId = $this->doctor->facility_id ?? null;
        if (!$facId) return null;

        $address = DB::table('facilities as fac')
            ->leftJoin('regions as reg', 'reg.reg_psgc', '=', 'fac.reg_psgc')
            ->leftJoin('provinces as prov','prov.prov_psgc','=', 'fac.prov_psgc')
            ->leftJoin('municipal_cities as mun','mun.muni_psgc','=', 'fac.muni_psgc')
            ->leftJoin('barangays as brgy','brgy.brg_psgc','=', 'fac.brgy_psgc')
            ->where('fac.id', $facId)
            ->select(
                DB::raw("CONCAT_WS(', ', brgy.brg_name, mun.muni_name, prov.prov_name, reg.reg_desc, fac.facilityname) as full_address")
            )
            ->first();

        return $address ? $address->full_address : null;
    }
    
}
