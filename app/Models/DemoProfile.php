<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class DemoProfile extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'tele_demographic_profile';
    protected $guarded = array();
}
