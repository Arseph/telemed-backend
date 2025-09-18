<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class UnitofMes extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'ref_unitofmes';
    protected $guarded = array();
}
