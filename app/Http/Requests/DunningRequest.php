<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DunningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'dunning_type'     => ['required', 'in:first,second,final,legal'],
            'method'           => ['required', 'in:email,phone,mail,visit'],
            'next_action_date' => ['nullable', 'date', 'after:today'],
            'content'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'dunning_type.required' => '督促種別を選択してください。',
            'dunning_type.in'       => '督促種別が不正です。',
            'method.required'       => '督促方法を選択してください。',
            'method.in'             => '督促方法が不正です。',
        ];
    }
}
