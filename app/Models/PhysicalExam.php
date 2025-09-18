<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PhysicalExam extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'tele_physical_exams';
    protected $guarded = array();
}
