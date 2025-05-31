<?php

namespace App\Http\Requests\Delegation\Delegation;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the Delegation is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     * Rule::unique('users')->ignore($this->route('user'))
     */

   
    public function rules(): array
    {
        return [
            'delegate_id' => 'gt:0'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('delegation')
        ]);
    }

}
