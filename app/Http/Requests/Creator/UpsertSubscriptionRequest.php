<?php

namespace App\Http\Requests\Creator;

use Illuminate\Foundation\Http\FormRequest;

class UpsertSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // optionally add a policy to require "creator" capability
    }

    public function rules(): array
    {
        return [
            'title'       => ['required','string','max:120'],
            'description' => ['nullable','string','max:2000'],
            'price'       => ['required','numeric','min:1','max:9999.99'],
            'interval'    => ['required','in:month,year'],
        ];
    }
}
