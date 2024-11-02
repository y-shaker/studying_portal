<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Course $course)
    {
        return $course->lessons;
    }

    public function store(Request $request, Course $course)
    {
        $lesson = $course->lessons()->create($request->all());
        return response()->json($lesson, 201);
    }

    public function show(Lesson $lesson)
    {
        return $lesson;
    }

    public function update(Request $request, Lesson $lesson)
    {
        $lesson->update($request->all());
        return response()->json($lesson);
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();
        return response()->json(null, 204);
    }

    public function comments(Lesson $lesson)
    {
        $comments = $lesson->comments;
        return response()->json($comments);
    }

    public function courseLessons(Course $course)
    {
        $lessons = $course->lessons;
        return response()->json($lessons);
    }

}
