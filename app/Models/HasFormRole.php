<?php

namespace App\Models\Concerns;

trait HasFormRole
{
    public const ROLE_REQUESTER = 'requester';
    public const ROLE_ACCEPTER  = 'accepter';

    // Laravel calls initialize<TraitName>() when the model is constructed.
    // Only extend $fillable if the model uses one. With $guarded = [] the
    // model is already open, and filling $fillable would lock every other field.
    public function initializeHasFormRole(): void
    {
        if (! empty($this->getFillable())) {
            $this->mergeFillable(['form_role', 'user_id']);
        }
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('form_role', $role);
    }
}
