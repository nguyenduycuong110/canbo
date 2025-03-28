<?php

namespace App\Http\Requests\User\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'account' => 'required|string|min:6',
            'email' => 'required|string|email|unique:users|max:191',
            'name' => 'required|string',
            'cid' => 'required',
            'user_catalogue_id'=> 'gt:0',
            'password' => 'required|string|min:6',
            're_password' => 'required|string|same:password',
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

    public function messages(): array{
        return [
            'account.required' => 'Bạn chưa nhập :attribute',
            'account.string' => 'Tên :attribute phải là kiểu chuỗi',
            'account.min' => 'Tên :attribute phải có tối thiểu 6 ký tự',
            'email.required' => 'Bạn chưa nhập :attribute',
            'email.string' => 'Tên :attribute phải là kiểu chuỗi',
            'email.email' => 'Email chưa đúng định dạng. Ví dụ: abc@gmail.com',
            'email.unique' => 'Email đã tồn tại. Hãy chọn email khác',
            'email.max' => 'Độ dài :attribute tối đa 191 ký tự',
            'name.required' => 'Bạn chưa nhập :attribute',
            'name.string' => 'Tên :attribute phải là kiểu chuỗi',
            'cid.required' => 'Bạn chưa nhập :attribute',
            'user_catalogue_id.gt' => 'Bạn chưa nhập :attribute',
            'password.required' => 'Bạn chưa nhập vào :attribute',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự',
            're_password.required' => 'Bạn phải nhập vào ô :attribute',
            're_password.same' => 'Mật khẩu không khớp',
            'team_id.gt' => 'Bạn chưa nhập :attribute',
            'unit_id.gt' => 'Bạn chưa nhập :attribute',
        ];
    }

    protected function prepareForValidation(){
        $managers = $this->input('managers', []);
        if(!is_array($managers)){
            $managers = [];
        }

        $parentId = $this->input('parent_id');
        if(!is_null($parentId) && !in_array($parentId, $managers)){
            $managers[] = (int) $parentId;
        }
        $this->merge([
            'managers' => $managers
        ]);
    }
}
