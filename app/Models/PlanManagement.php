<?php

namespace App\Models;

use App\Models\Concerns\HasFormRole;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PlanManagement extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, HasFormRole;

    protected $table = 'tele_plan_management';
    protected $guarded = array();
}
