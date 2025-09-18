<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Region extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'regions';
    protected $guarded = array();
}
