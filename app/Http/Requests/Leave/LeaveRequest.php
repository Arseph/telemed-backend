<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
class LeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if(Auth::user())
            return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'location' => ['nullable', 'string', 'max:255'],
			'abroad_destination' => ['nullable', 'string', 'max:255'],
			'sick_leave_type' => ['nullable', 'string', 'max:255'],
            'illness_details' => ['nullable', 'string', 'max:255'],
			'study_leave_type' => ['nullable', 'string', 'max:255'],
			'disapproval_reason' => ['nullable', 'string', 'max:255'],

            'type_of_leave' => ['required', 'string', 'max:255'],
            'working_days' => ['integer'],
            'commutation' => ['required', 'string', 'max:255'],
            'remaining_leave_credits' => ['required', 'string', 'max:255'],
            'recommendation' => ['required', 'string', 'max:255'],
            'inclusive_days' => ['required', 'array'],
            'inclusive_days.*' => ['date'],
        ];
    }
}

