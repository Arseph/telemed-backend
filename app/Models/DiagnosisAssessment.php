<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class DiagnosisAssessment extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'tele_diagnosis_assessment';
    protected $guarded = array();
}
