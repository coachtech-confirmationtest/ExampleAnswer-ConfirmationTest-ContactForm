<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
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
            'keyword' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'integer', 'in:1,2,3'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.max' => 'キーワードは255文字以内で入力してください',
            'gender.in' => '性別の値が不正です',
            'category_id.exists' => '選択されたカテゴリーが存在しません',
            'date.date' => '日付の形式が正しくありません',
            'per_page.max' => '1ページあたりの件数は100件以内で指定してください',
        ];
    }
}
