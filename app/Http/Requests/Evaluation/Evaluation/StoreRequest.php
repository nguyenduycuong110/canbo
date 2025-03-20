<?php

namespace App\Http\Requests\Evaluation\Evaluation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the Evaluation is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'task_id' => 'gt:0',
            'start_date' => 'date',
            'due_date' => 'date',
            // 'completion_date' => 'required',
            'status_id' => 'gt:0',
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            'statuses' => [
                [
                    'user_id' => Auth::id(),
                    'status_id' => $this->status_id,
                    'lock' => 0
                ]
            ]
        ]);
    }
    
}
