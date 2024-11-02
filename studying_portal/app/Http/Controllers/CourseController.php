<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('lessons')->get();
        return response()->json($courses);
    }

    // Display a single course
    public function show($id)
    {
        $course = Course::with('lessons')->findOrFail($id);
        return response()->json($course);
    }

    // Create a new course
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lessons' => 'required|array',
            'lessons.*.title' => 'required|string|max:255',
            'lessons.*.content' => 'required|string',
        ]);

        // Create the course
        $course = Course::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
        ]);

        // Create the lessons and associate them with the course
        foreach ($validatedData['lessons'] as $lessonData) {
            $lesson = new Lesson($lessonData);
            $course->lessons()->save($lesson);
        }

        return response()->json($course->load('lessons'), 201);
    }

    // Update an existing course
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'lessons' => 'sometimes|array',
            'lessons.*.title' => 'sometimes|required|string|max:255',
            'lessons.*.content' => 'sometimes|required|string',
        ]);

        $course->update($validatedData);

        return response()->json($course->load('lessons'));
    }

    // Delete a course
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(null, 204);
    }
}
