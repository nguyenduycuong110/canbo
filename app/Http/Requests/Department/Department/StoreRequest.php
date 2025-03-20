<?php

namespace App\Http\Requests\Department\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the Department is authorized to make this request.
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
            'name' => 'string|required'
        ];
    }
    
    public function attributes(): array {
        return [
            'name' => 'Cục',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Tên cục phải là kiểu chuỗi.',
            'name.required' => 'Bạn chưa nhập tên cục.',
        ];
    }

}
