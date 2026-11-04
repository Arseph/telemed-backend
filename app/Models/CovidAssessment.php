<?php

namespace App\Models;

use App\Models\Concerns\HasFormRole;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class CovidAssessment extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, HasFormRole;

    protected $table = 'tele_covid19_clinical_assessment';
    protected $guarded = array();
}
