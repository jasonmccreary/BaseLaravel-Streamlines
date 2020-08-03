<?php

namespace App\Http\Requests;

use App\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoice extends FormRequest
{
    public function rules()
    {
        return [
            'order_id' => ['required', Rule::exists('orders')->where('user_id', $this->user()->id)],
            'billing_details' => 'required|string',
            'tax_id' => 'nullable|string',
        ];
    }

    public function createInvoice()
    {
        return Invoice::create($this->validated());
    }
}
