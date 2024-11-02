<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Comment;
use App\Models\Achievement;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Display a single user
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // Create a new user
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
            ]);

            return response()->json($user, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    // Update an existing user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        if (isset($validatedData['name'])) {
            $user->name = $validatedData['name'];
        }
        if (isset($validatedData['email'])) {
            $user->email = $validatedData['email'];
        }
        if (isset($validatedData['password'])) {
            $user->password = bcrypt($validatedData['password']);
        }

        $user->save();

        return response()->json($user);
    }

    // Delete a user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }

    public function userBadge(User $user)
    {
        $achievementCount = $user->achievements_count;

        $badges = [
            'Beginner' => 0,
            'Intermediate' => 4,
            'Advanced' => 8,
            'Master' => 10,
        ];
        $next_badge = "";
        $next_threshold = 0;
        $remaining_to_unlock_next_badge = 0;

        $current_badge = 'Beginner';
        foreach ($badges as $badge => $threshold) {
            if ($achievementCount >= $threshold) {
                $current_badge = $badge;
            }
        }

        $badgeKeys = array_keys($badges);

        $badge_index = array_search($current_badge, $badgeKeys);

        // Determine the next badge
        if ($badge_index < count($badgeKeys) - 1) {
            $next_badge = $badgeKeys[$badge_index + 1];
            $next_threshold = $badges[$next_badge];
        }
        if ($next_threshold != 0) 
            $remaining_to_unlock_next_badge = $next_threshold - $achievementCount;

            return response()->json(['current_badge' => $current_badge, 'next_badge' =>$next_badge, 'remaining_to_unlock_next_badge' => $remaining_to_unlock_next_badge]);
        

    }


    public function enrollCourse(User $user, Course $course)
    {
        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return response()->json(['message' => 'User is already enrolled in this course.'], 409);
        }

        $user->courses()->attach($course->id);


        return response()->json($user->courses, 201);
    }

    public function listenLesson(User $user, Lesson $lesson)
    {
        // Check if the user is enrolled in the course associated with the lesson
        if (!$user->courses()->where('course_id', $lesson->course_id)->exists()) {
            return response()->json(['message' => 'User is not enrolled in the course associated with this lesson.'], 403);
        }

        // Check if the user has already listened to the lesson
        if ($user->lessons()->where('lesson_id', $lesson->id)->exists()) {
            return response()->json(['message' => 'User has already listened to this lesson.'], 409);
        }

        $user->lessons()->attach($lesson->id);

        app(UserController::class)->calculateAchievements($user);

        return response()->json($user->lessons, 201);
    }

    public function calculateAchievements(User $user)
    {
        $lessonsWatchedCount = $user->lessons()->count();
        $commentsWrittenCount = $user->comments()->count();

        $unlocked_achievements = [];
        $next_available_achievements = [];
        $lesson_index = 0;
        $comment_index = 0;

        // Calculate lessons watched achievements
        $lessonAchievementKeys = array_keys(Achievement::$lessonsWatchedAchievements);
        foreach (Achievement::$lessonsWatchedAchievements as $name => $threshold) {
            if ($lessonsWatchedCount >= $threshold) {
                $unlocked_achievements[] = $name;
                $lesson_index = array_search($name, $lessonAchievementKeys);
            }
        }

        if ($lesson_index < count($lessonAchievementKeys) - 1 && $lessonsWatchedCount != 0) {
            $next_available_achievements[] = $lessonAchievementKeys[$lesson_index + 1];
        } else if ($lesson_index == 0) {
            $next_available_achievements[] = $lessonAchievementKeys[0];
        }

        // Calculate comments written achievements
        $commentAchievementKeys = array_keys(Achievement::$commentsWrittenAchievements);
        foreach (Achievement::$commentsWrittenAchievements as $name => $threshold) {
            if ($commentsWrittenCount >= $threshold) {
                $unlocked_achievements[] = $name;
                $comment_index = array_search($name, $commentAchievementKeys);
            }
        }

        if ($comment_index < count($commentAchievementKeys) - 1 && $commentsWrittenCount != 0) {
            $next_available_achievements[] = $commentAchievementKeys[$comment_index + 1];
        } else if ($comment_index == 0) {
            $next_available_achievements[] = $commentAchievementKeys[0];
        }

        $user->achievements_count = count($unlocked_achievements);
        $user->save();

        return response()->json([
            'unlocked_achievements' => $unlocked_achievements,
            // 'lessons_watched_count' => $lessonsWatchedCount,
            // 'comments_written_count' => $commentsWrittenCount,
            // 'lesson_index' => $lesson_index,
            // 'comment_index' => $comment_index,
            'next_available_achievements' => $next_available_achievements
        ]);
    }


}
