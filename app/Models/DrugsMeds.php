<?php

namespace App\Models;

use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class DrugsMeds extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'ref_drugsmeds';
    protected $guarded = array();

}
