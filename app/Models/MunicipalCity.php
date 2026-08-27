<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class MunicipalCity extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'municipal_cities';
    protected $guarded = array();

    /**
     * The province this city or municipality belongs to.
     *
     * See Province::municipalCities() — the two prov_psgc columns are different
     * types (int here, varchar there), so comparisons outside SQL must normalise.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'prov_psgc', 'prov_psgc');
    }
}
