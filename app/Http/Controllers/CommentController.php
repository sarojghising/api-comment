<?php

namespace App\Http\Controllers;

use App\Exceptions\CommentException;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the authenticated user's comments.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $comments = Auth::user()->comments;

        return $this->successResponse(
            CommentResource::collection($comments),
            'Comments retrieved successfully'
        );
    }

    /**
     * Store a newly created comment in storage.
     *
     * @param  \App\Http\Requests\StoreCommentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCommentRequest $request)
    {
        return $this->executeCommentOperation(function () use ($request) {
            $comment = Auth::user()->comments()->create($request->validated());
            return new CommentResource($comment);
        }, 'Comment created successfully', 201);
    }

    /**
     * Update the specified comment in storage.
     *
     * @param  \App\Http\Requests\UpdateCommentRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCommentRequest $request, $id)
    {
        return $this->executeCommentOperation(function () use ($request, $id) {
            $comment = $this->findUserComment($id);

            if (!$comment) {
                return $this->errorResponse('Comment not found', 404);
            }

            $comment->update($request->validated());
            return new CommentResource($comment);
        }, 'Comment updated successfully');
    }

    /**
     * Remove the specified comment from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $comment = $this->findUserComment($id);

        if (!$comment) {
            return $this->errorResponse('Comment not found', 404);
        }

        $comment->delete();

        return $this->successResponse(null, 'Comment deleted successfully', 204);
    }

    /**
     * Find a comment that belongs to the authenticated user.
     *
     * @param  int  $id
     * @return \App\Models\Comment|null
     */
    private function findUserComment($id)
    {
        return Auth::user()->comments()->find($id);
    }

    /**
     * Execute a database operation with transaction support and error handling.
     *
     * @param  callable  $operation
     * @param  string  $successMessage
     * @param  int  $successStatusCode
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\CommentException
     */
    private function executeCommentOperation(callable $operation, string $successMessage, int $successStatusCode = 200)
    {
        DB::beginTransaction();

        try {
            $result = $operation();

            // If the operation returned a response, return it directly
            if ($result instanceof \Illuminate\Http\JsonResponse) {
                DB::rollBack();
                return $result;
            }

            DB::commit();
            return $this->successResponse($result, $successMessage, $successStatusCode);

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logException($th, 'Comment Operation');
            throw new CommentException('An error occurred while processing the comment', 500);
        }
    }

    /**
     * Log exception details.
     *
     * @param  \Throwable  $exception
     * @param  string  $operation
     * @return void
     */
    private function logException(\Throwable $exception, string $operation)
    {
        Log::info("=================== {$operation} =================");
        Log::error('Exception occurred:', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
        Log::info("=================== End {$operation} =================");
    }
}
