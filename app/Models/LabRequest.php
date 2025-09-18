<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class LabRequest extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'ref_labrequest';
    protected $guarded = array();
}
