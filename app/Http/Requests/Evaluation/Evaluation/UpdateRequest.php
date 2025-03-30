<?php

namespace App\Http\Requests\Evaluation\Evaluation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateRequest extends FormRequest
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
     * Rule::unique('users')->ignore($this->route('user'))
     */

   
    public function rules(): array
    {
        return [
            'task_id' => 'gt:0',
            'start_date' => 'date',
            'due_date' => 'date',
            'status_id' => 'gt:0',
            'file' => [
                'file',    
                'mimes:pdf,xlsx,doc,docx,rar,zip', 
                'max:5120'  
            ],
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

    public function messages(): array
    {
        return [
            'file.file' => 'Dữ liệu upload phải là một file.',
            'file.mimes' => 'File chỉ chấp nhận định dạng: pdf, xlsx, doc, docx, rar, zip.',
            'file.max' => 'Kích thước file không được vượt quá 5MB.',
        ];
    }
}
