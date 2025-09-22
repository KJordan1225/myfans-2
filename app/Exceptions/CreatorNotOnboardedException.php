<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CreatorNotOnboardedException extends Exception
{
    public function __construct(string $message = 'Creator is not onboarded to Stripe Connect.')
    {
        parent::__construct($message);
    }

    /**
     * Optionally customize the HTTP status code for API responses.
     */
    public function status(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY; // 422
    }

    /**
     * Render the exception into an HTTP response.
     * - For API/AJAX: return JSON
     * - For Web: flash SweetAlert payload and redirect back
     */
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error'   => class_basename($this),
                'message' => $this->getMessage(),
            ], $this->status());
        }

        // Web flow: flash SweetAlert config
        return back()->with('swal', [
            'icon'  => 'warning',
            'title' => 'Action needed',
            'text'  => $this->getMessage(),
        ]);
    }
}
