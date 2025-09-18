<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class CovidScreening extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'tele_covid19_screening';
    protected $guarded = array();
}
