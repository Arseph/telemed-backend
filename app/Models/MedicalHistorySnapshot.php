<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A point-in-time copy of a patient's medical history and problem list.
 *
 * Deliberately NOT Auditable: these rows are written once and never edited, so an
 * audit trail on top of them would record nothing. The thing worth auditing is the
 * live record, which already is.
 */
class MedicalHistorySnapshot extends Model
{
    protected $table = 'medical_history_snapshots';

    /** taken_at is set explicitly; there are no created_at/updated_at columns. */
    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'meeting_id',
        'taken_by',
        'reason',
        'payload',
        'taken_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'taken_at' => 'datetime',
    ];

    public const REASON_HISTORY = 'history';
    public const REASON_PROBLEM = 'problem';

    /**
     * Who made the change, when it could be resolved.
     *
     * Named user() rather than takenBy() because Laravel serialises takenBy to
     * "taken_by", which collides with the foreign key column of that name.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by', 'id');
    }

    /** The consultation this happened during, when it happened during one. */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Teleconsult::class, 'meeting_id', 'id');
    }
}
