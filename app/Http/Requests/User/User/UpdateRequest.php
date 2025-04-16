<?php

namespace App\Http\Requests\User\User;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
     * Rule::unique('users')->ignore($this->route('user'))
     */

   
    public function rules(): array
    {
        return [
            'account' => [
                'required',
                'string',
                'min:4',
            ],
            'email' => 'nullable|string|email|max:191',
            'name' => 'required|string',
            'cid' => 'required',
            'user_catalogue_id'=> 'gt:0',
            'team_id' => 'gt:0',
            'unit_id' => 'gt:0',
        ];
    }

    
    public function attributes(): array{
        return [
            'account' => 'tài khoản',
            'name' => 'Họ tên',
            'cid' => 'Chứng minh thư',
            'user_catalogue_id' => 'Chức vụ',
            'password' => 'mật khẩu',
            're_password' => 'nhập lại mật khẩu' ,
            'team_id' => 'Đội',
            'unit_id' => 'Phòng / chi cục',
        ];
    }

    protected function prepareForValidation()
    {
        $managers = $this->input('managers', []);
        
        if(!is_array($managers)){
            $managers = [];
        }

        $parentId = $this->input('parent_id');
        if(!is_null($parentId) && !in_array($parentId, $managers) && $parentId != 0){
            $managers[] = (int) $parentId;
        }

        if($parentId != 0){
            $this->merge([
                'id' => $this->route('user'),
                'managers' => $managers
            ]);
        }
        
    }

    public function messages(): array{
        return [
            'account.required' => 'Bạn chưa nhập :attribute',
            'account.string' => 'Tên :attribute phải là kiểu chuỗi',
            'account.min' => 'Tên :attribute phải có tối thiểu 4 ký tự',
            'email.required' => 'Bạn chưa nhập :attribute',
            'email.string' => 'Tên :attribute phải là kiểu chuỗi',
            'email.email' => 'Email chưa đúng định dạng. Ví dụ: abc@gmail.com',
            'email.max' => 'Độ dài :attribute tối đa 191 ký tự',
            'name.required' => 'Bạn chưa nhập :attribute',
            'name.string' => 'Tên :attribute phải là kiểu chuỗi',
            'cid.required' => 'Bạn chưa nhập :attribute',
            'user_catalogue_id.gt' => 'Bạn chưa nhập :attribute',
            'password.required' => 'Bạn chưa nhập vào :attribute',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự',
            'team_id.gt' => 'Bạn chưa nhập :attribute',
            'unit_id.gt' => 'Bạn chưa nhập :attribute',
        ];
    }

  

}
