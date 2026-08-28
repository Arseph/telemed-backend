<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Province extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'provinces';
    protected $guarded = array();

    /**
     * Cities and municipalities within this province.
     *
     * Joined on the PSGC code rather than `id` because that is what both tables
     * actually carry. Note the column types differ — provinces.prov_psgc is a
     * varchar (and keeps its leading zero, e.g. "012800000") while
     * municipal_cities.prov_psgc is an unsigned int. MySQL coerces the two for
     * comparison, but anything matching these values in PHP or JavaScript has to
     * normalise them first; string equality will silently fail for any province
     * whose code starts with a zero.
     */
    public function municipalCities(): HasMany
    {
        return $this->hasMany(MunicipalCity::class, 'prov_psgc', 'prov_psgc');
    }
}
