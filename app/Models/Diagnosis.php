<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Diagnosis extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'diagnosis';
    protected $guarded = array();
}
