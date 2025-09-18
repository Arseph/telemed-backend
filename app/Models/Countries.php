<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Countries extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'countries';
    protected $guarded = array();
}
