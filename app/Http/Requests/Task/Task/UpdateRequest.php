<?php

namespace App\Http\Requests\Task\Task;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the Task is authorized to make this request.
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
            'name' => 'required|string',
        ];
    }
 
    public function attributes(): array{
        return [
            'name' => 'công việc'
        ];
    }
 
    public function messages(): array
    {
        return [
            'name.required' => 'Bạn chưa nhập :attribute',
            'name.string' => 'Tên :attribute phải là chuỗi'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('work')
        ]);
    }

}
