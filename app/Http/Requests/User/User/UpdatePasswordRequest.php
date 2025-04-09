<?php

namespace App\Http\Requests\User\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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
        return [
            
            'password' => 'required|string|min:6',
            're_password' => 'required|string|same:password',
        ];
    }

    public function attributes(): array{
        return [
            'password' => 'mật khẩu',
            're_password' => 'nhập lại mật khẩu' ,
        ];
    }

    public function messages(): array{
        return [
            'password.required' => 'Bạn chưa nhập vào :attribute',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự',
            're_password.required' => 'Bạn phải nhập vào ô :attribute',
            're_password.same' => 'Mật khẩu không khớp',
        ];
    }

}
