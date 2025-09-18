<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class DiagMainCat extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'diagnosis_main_categories';
    protected $guarded = array();
}
