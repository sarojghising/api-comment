<?php

namespace App\Exceptions;

use Exception;

class CommentException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'comment' => $this->getMessage(),
            ],
        ], $this->getCode() ?: 400);
    }
}
