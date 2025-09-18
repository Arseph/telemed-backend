<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Audit extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'audits';
    protected $guarded = array();
}
