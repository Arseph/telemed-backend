<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * One condition on a patient's problem list.
 *
 * Auditable like the other clinical models, so adding, resolving or correcting a
 * problem is attributable — see config/audit.php.
 */
class PatientProblem extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'patient_problems';

    protected $fillable = [
        'patient_id',
        'diagnosis_id',
        'problem',
        'onset_date',
        'status',
        'resolved_date',
        'notes',
    ];

    protected $casts = [
        'onset_date' => 'date:Y-m-d',
        'resolved_date' => 'date:Y-m-d',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RESOLVED = 'resolved';

    /** The ICD-10 entry, when the problem was picked from the reference list. */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class, 'diagnosis_id', 'id');
    }

    /**
     * What to show for this problem: the ICD-10 description when there is one,
     * otherwise the free text typed instead.
     */
    public function getLabelAttribute(): string
    {
        return $this->diagnosis?->diagdesc ?? (string) $this->problem;
    }
}
