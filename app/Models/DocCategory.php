<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class DocCategory extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'doctor_categories';
    protected $guarded = array();
}
