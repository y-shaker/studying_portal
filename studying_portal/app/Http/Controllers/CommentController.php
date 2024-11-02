<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Lesson $lesson)
    {
        return $lesson->comments;
    }

    public function store(Request $request, User $user, Lesson $lesson)
    {
        // Check if the user is enrolled in the course associated with the lesson
        if (!$user->courses()->where('course_id', $lesson->course_id)->exists()) {
            return response()->json(['message' => 'User is not enrolled in the course associated with this lesson.'], 403);
        }

        // Check if the user has already commented on this lesson
        if ($lesson->comments()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User has already commented on this lesson.'], 409);
        }
        
        // Validate the request data
        $validatedData = $request->validate([
            'content' => 'required|string',
        ]);

        // Create a new comment with the validated data
        $comment = new Comment([
            'content' => $validatedData['content'],
        ]);

        // Associate the comment with the user and lesson
        $comment->user()->associate($user);
        $comment->lesson()->associate($lesson);

        // Save the comment
        $comment->save();

        // Calculate achievements
        app(UserController::class)->calculateAchievements($user);

        return response()->json($comment, 201);
    }

    public function show(Comment $comment)
    {
        return $comment;
    }

    public function update(Request $request, Comment $comment)
    {
        $comment->update($request->all());
        return response()->json($comment);
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response()->json(null, 204);
    }
}
