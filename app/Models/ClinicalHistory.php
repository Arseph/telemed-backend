<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class ClinicalHistory extends Model implements Auditable
{
	use \OwenIt\Auditing\Auditable;
    protected $table = 'tele_clinical_histories';
    protected $guarded = array();
}
