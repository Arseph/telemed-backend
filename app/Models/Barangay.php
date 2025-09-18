<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Barangay extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'barangays';
    protected $guarded = array();
}
