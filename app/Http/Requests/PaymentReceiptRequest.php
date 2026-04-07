<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'invoice_id'      => ['required', 'integer', 'exists:invoices,id'],
            'received_amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'received_date'   => ['required', 'date', 'before_or_equal:today'],
            'payment_method'  => ['required', 'in:bank_transfer,credit_card,cash,other'],
            'bank_name'       => ['nullable', 'string', 'max:100'],
            'bank_account'    => ['nullable', 'string', 'max:100'],
            'memo'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required'      => '請求書を選択してください。',
            'invoice_id.exists'        => '指定された請求書が見つかりません。',
            'received_amount.required' => '入金額を入力してください。',
            'received_amount.min'      => '入金額は1円以上で入力してください。',
            'received_date.required'   => '入金日を入力してください。',
            'received_date.before_or_equal' => '入金日は今日以前の日付を入力してください。',
            'payment_method.required'  => '入金方法を選択してください。',
            'payment_method.in'        => '入金方法が不正です。',
        ];
    }
}
