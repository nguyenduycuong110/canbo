<?php

namespace App\Http\Requests\User\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the User is authorized to make this request.
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
    $rules = [
        'email' => [
            'nullable',
            'string',
            'email',
            'max:191',
            Rule::unique('users')->ignore($this->user()->id)
        ],
        'name' => 'string',
        'user_catalogue_id'=> 'gt:0',
    ];

        if ($this->filled('password')) {
            $rules['password'] = 'string|min:6';
            $rules['re_password'] = 'required|string|same:password';
        }

        return $rules;
    }

    public function attributes(): array{
        return [
            'name' => 'Họ tên',
            'cid' => 'Chứng minh thư',
            'user_catalogue_id' => 'Chức vụ',
            'password' => 'mật khẩu',
            're_password' => 'nhập lại mật khẩu' ,
        ];
    }

    public function messages(): array{
        return [
            'email.string' => 'Tên :attribute phải là kiểu chuỗi',
            'email.email' => 'Email chưa đúng định dạng. Ví dụ: abc@gmail.com',
            'email.max' => 'Độ dài :attribute tối đa 191 ký tự',
            'name.string' => 'Tên :attribute phải là kiểu chuỗi',
            'user_catalogue_id.gt' => 'Bạn chưa nhập :attribute',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự',
            're_password.required' => 'Bạn phải nhập vào ô :attribute',
            're_password.same' => 'Mật khẩu không khớp',
        ];
    }

}
