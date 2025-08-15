<?php

namespace App\Http\Requests\Post;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user(); // returns the currently authenticated user

        // Must be logged in, have the "creator" role, and their profile must exist and be marked as creator
        return $user 
            && $user->hasRole('creator') 
            && $user->profile 
            && $user->profile->is_creator;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'media_type' => ['required', 'in:image,video'],
			'price' => [
				'nullable', // Allows the field to be null or not present
				'numeric',  // Ensures it's a valid number (integer or float)
				'min:0',    // Prices typically can't be negative. Adjust if your logic allows negative prices.
				'max:99999999.99', // Matches the precision (10 total digits, 2 after decimal)
				'regex:/^\d+(\.\d{1,2})?$/', // Ensures at most 2 decimal places
			],
			'is_paid' => 'boolean',
			'visibility' => ['required', 'in:public,subscribers,paid'],
            'image' => [
                'nullable',
                'required_without:video',      // require if no video
                'image',
                'max:5120'                      // 5MB in KB
            ],
            'video' => [
                'nullable',
                'required_without:image',      // require if no image
                'file',
                'mimetypes:video/mp4,video/quicktime',
                'max:51200'                     // 50MB in KB
            ],

        ];
    }
}
