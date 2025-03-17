<?php

namespace App\Http\Controllers;

use App\Exceptions\CommentException;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\CommentResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $user = Auth::user();
        $comments = $user->comments;

        return $this->successResponse(new CommentCollection($comments), 'Comments retrieved successfully');
    }

    // Create a new comment
    public function store(StoreCommentRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $comment = $user->comments()->create($request->validated());
            DB::commit();
            return $this->successResponse(new CommentResource($comment), 'Comment created successfully', 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info("=================== Store Comment =================");
            Log::error('Exception occurred:', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            Log::info("=================== End Store Comment =================");
            throw new CommentException('An error occurred while creating the comment', 500);
        }
    }


    public function update(UpdateCommentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $comment = $user->comments()->findOrFail($id);
            $comment->update($request->validated());
            DB::commit();
            return $this->successResponse(new CommentResource($comment), 'Comment updated successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info("=================== Update Comment =================");
            Log::error('Exception occurred:', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            Log::info("=================== End Update Comment =================");
            throw new CommentException('An error occurred while updating the comment', 500);
        }
    }

    // Delete a comment (Bonus)
    public function destroy($id)
    {
        $user = Auth::user();
        $comment = $user->comments()->find($id);
        if (!$comment) {
            return $this->errorResponse('Comment not found', 404);
        }
        $comment->delete();

        return $this->successResponse(null, 'Comment deleted successfully', 204);
    }
}
