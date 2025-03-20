<?php

namespace App\Http\Requests\Department\Department;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the Department is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'string|required',
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

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('department')
        ]);
    }

}
