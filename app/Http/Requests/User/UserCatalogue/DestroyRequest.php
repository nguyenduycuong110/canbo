<?php

namespace App\Http\Requests\User\UserCatalogue;

use Illuminate\Foundation\Http\FormRequest;

class DestroyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'id' => 'required|exists:user_catalogues',
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            'id' => $this->route('user_catalogue')
        ]);
    }
}
