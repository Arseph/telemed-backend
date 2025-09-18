<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'abroad_destination' => $this->abroad_destination,
            'sick_leave_type' => $this->sick_leave_type,
            'illness_details' => $this->illness_details,
            'study_leave_type' => $this->study_leave_type,
            'disapproval_reason' => $this->disapproval_reason,

            'type_of_leave' => $this->type_of_leave,
            'working_days' => $this->working_days,
            'commutation' => $this->commutation,
            'remaining_leave_credits' => $this->remaining_leave_credits,
            'recommendation' => $this->recommendation,
            'inclusive_days' => $this->inclusive_days
        ];
    }
}
