<?php

namespace App\Http\Requests\User\UserCatalogue;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'name' => 'string|required',
            'level' => 'gt:0',
            'can_create_tasks' => 'gt:0'
        ];
    }

    public function attributes(): array {
        return [
            'name' => 'Chức vụ',
            'level' => 'Cấp bậc',
            'can_create_tasks' => 'Quyền tạo công việc'
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => ':attribute phải là kiểu chuỗi.',
            'name.required' => 'Bạn chưa nhập chức vụ.',
            'level.gt' => 'Bạn chưa chọn :attribute.',
            'can_create_tasks.gt' => 'Bạn chưa chọn :attribute.'
        ];
    }
}
