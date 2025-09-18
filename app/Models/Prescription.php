<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Prescription extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'trans_prescription';
    protected $guarded = array();

    public function drugmed() {
        return $this->hasOne(DrugsMeds::class, 'id', 'drug_id');
    }

    public function prescribe() {
        return $this->hasOne(User::class, 'id', 'prescribebyid');
    }

    public function type_med() {
    	if($this->type_of_medicine == '1') {
        	return 'General Drug';
        } else {
        	return 'Specific Facility Druglist';
        }
    }
    public function freq() {
        if($this->frequency == 'D') {
        	return 'DAILY';
        } if($this->frequency == 'I') {
        	return 'INDEFINITE';
        } if($this->frequency == 'M') {
        	return 'MONTHLY';
        } if($this->frequency == 'O') {
        	return 'OTHERS';
        } if($this->frequency == 'Q') {
        	return 'QUARTERLY';
        } if($this->frequency == 'W') {
        	return 'WEEKLY';
        } if($this->frequency == 'Y') {
        	return 'YEARLY';
        } 
    }

    public function dose_reg() {
    	if($this->dose_regimen == 'BID') {
        	return '2X A DAY EVERY 12 HOURS';
        } if($this->dose_regimen == 'TID') {
        	return '3 X A DAY - EVERY 8 HOURS';
        } if($this->dose_regimen == 'QID') {
        	return '4 X A DAY - EVERY 6 HOURS';
        } if($this->dose_regimen == 'QHS') {
        	return 'EVERY BEDTIME';
        } if($this->dose_regimen == 'QOD') {
        	return 'EVERY OTHER DAY';
        } if($this->dose_regimen == 'OD') {
        	return 'ONCE A DAY';
        } if($this->dose_regimen == 'OTH') {
        	return 'OTHERS';
        }
    }

    public function encoded() {
        return $this->hasOne(User::class, 'id', 'encodedby');
    }
    public function modified() {
        return $this->hasOne(User::class, 'id', 'modifyby');
    }
}
