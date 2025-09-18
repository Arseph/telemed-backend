<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Facility extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'facilities';
    protected $guarded = array();

    public function region() {
        return $this->hasOne(Region::class, 'reg_psgc', 'reg_psgc');
    }
    public function province() {
    	return $this->hasOne(Province::class, 'prov_psgc', 'prov_psgc');
    }
    public function municipal() {
    	return $this->hasOne(MunicipalCity::class, 'muni_psgc', 'muni_psgc');
    }

    public function barangay() {
    	return $this->hasOne(Barangay::class, 'brg_psgc', 'brgy_psgc');
    }
    public function zoom() {
        return $this->hasOne(ZoomCredential::class, 'facility_id', 'id');
    }
}
