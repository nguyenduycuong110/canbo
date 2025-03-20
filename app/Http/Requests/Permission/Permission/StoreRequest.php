<?php

namespace App\Http\Requests\Permission\Permission;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the Permission is authorized to make this request.
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
            'name' => 'string|required|regex:/^[a-z_]+:[a-zA-Z]+$/|unique:permissions,id',
            'module' => 'string|required|not_in:none',
            'value' => 'required',
            'title' => 'string',
        ];
    }
    
    public function messages(): array {
        return [
            'name.regex' => 'Tên quyền phải theo cấu trúc ví dụ: permissions:index'
        ];
    }

    public function attributes(): array {
        return [
            'name' => 'Tên Quyền',
            'title' => 'Tiêu đề'
        ];
    }
}
