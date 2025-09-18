<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Doc_Type extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'ref_doctype';
    protected $guarded = array();
}
