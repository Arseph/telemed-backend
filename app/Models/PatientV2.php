<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class PatientV2 extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'tbl_master_patient';
    protected $guarded = array();
    
    public $timestamps = false; // disable default timestamp

    public function barangay() {
        return $this->belongsTo(Barangay::class, 'bgycode', 'brg_psgc');
    }

    public function nationality() {
    	return $this->hasOne(Countries::class, 'nationality', 'nationality');
    }

    public function account() {
        return $this->hasOne(User::class, 'id', 'userid');
    }
    public function meeting() {
        return $this->hasOne(PendingMeeting::class, 'patient_id', 'id');
    }
    
    public function allmeetings() {
        return $this->hasMany(Meeting::class, 'patient_id', 'id');
    }

    public function religion() {
        switch ($this->religion_code) {
            case 'AGLIP':
            return 'AGLIPAY';
            break;
            case 'ALLY' :
            return 'ALLIANCE OF BIBLE CHRISTIAN COMMUNITIES';
            case 'ANGLI' :
            return 'ANGLICAN';
            break;
            case 'BAPTI' :
            return 'AGLIPAY';
            break;
            case 'BRNAG' :
            return 'BORN AGAIN CHRISTIAN';
            break;
            case 'BUDDH' :
            return 'BUDDHISM';
            break;
            case 'CATHO' :
            return 'CATHOLIC';
            break;
            case 'XTIAN' :
            return 'CHRISTIAN';
            break;
            case 'CHOG' :
            return 'CHURCH OF GOD';
            break;
            case 'EVANG' :
            return 'EVANGELICAL';
            break;
            case 'IGNIK' :
            return 'IGLESIA NI CRISTO';
            break;
            case 'MUSLI' :
            return 'ISLAM';
            break;
            case 'JEWIT' :
            return 'JEHOVAHS WITNESS';
            break;
            case 'MORMO' :
            return 'LDS-MORMONS';
            break;
            case 'LRCM' :
            return 'LIFE RENEWAL CHRISTIAN MINISTRY';
            break;
            case 'LUTHR' :
            return 'LUTHERAN';
            break;
            case 'METOD' :
            return 'METHODIST';
            break;
            case 'PENTE' :
            return 'PENTECOSTAL';
            break;
            case 'PROTE' :
            return 'PROTESTANT';
            break;
            case 'SVDAY' :
            return 'SEVENTH DAY ADVENTIST';
            break;
            case 'UCCP' :
            return 'UCCP';
            break;
            case 'UNKNO' :
            return 'UNKNOWN';
            break;
            case 'WESLY' :
            return 'WESLEYAN';
            default:
                return 'UNKNOWN';
                break;
        }
    }
    
    public function edattain() {
        switch ($this->educattainment) {
            case '03':
                return 'COLLEGE';
                break;
            case '01':
                return 'ELEMENTARY EDUCATION';
                break;
            case '02':
                return 'HIGH SCHOOL EDUCATION';
                break;
            case '05':
                return 'NO FORMAL EDUCATION';
                break;
            case '06':
                return 'NOT APPLICABLE';
                break;
            case '04':
                return 'POSTGRADUATE PROGRAM';
                break;
            case '07':
                return 'VOCATIONAL';
                break;
            default:
                return 'NOT APPLICABLE';
                break;
        }
    }
    public function idtype() {
        switch ($this->id_type) {
            case 'umid':
                return 'UMID';
                break;
            case 'dl':
                return 'DRIVER\'S LICENSE';
                break;
            case 'passport':
                return 'PASSPORT ID';
                break;
            case 'postal':
                return 'POSTAL ID';
                break;
            case 'tin':
                return 'TIN ID';
                break;
            default:
                return 'NOT APPLICABLE';
                break;
        }
    }

    public function medhistory() {
        return $this->hasMany(MedicalHistory::class, 'patient_id', 'id');
    }

    // public function mydoctor() {
    //     return $this->hasOne(User::class, 'id', 'doctor_id');
    // }
}
