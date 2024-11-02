<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LessonController;

Route::get('users/{user}/achievements', [UserController::class, 'achievements']);
Route::apiResource('users', UserController::class);
Route::post('users/{user}/courses/{course}', [UserController::class, 'enrollCourse']);
Route::post('users/{user}/lessons/{lesson}', [UserController::class, 'listenLesson']);
Route::apiResource('courses', CourseController::class);
Route::post('users/{user}/lessons/{lesson}/comments', [CommentController::class, 'store']);
Route::get('users/{user}/achievements', [UserController::class, 'calculateAchievements']);
Route::get('users/{user}/badge', [UserController::class, 'userBadge']);
Route::apiResource('comments', CommentController::class);
Route::get('lessons/{lesson}/comments', [LessonController::class, 'comments']);
Route::apiResource('lessons', LessonController::class);
Route::get('courses/{course}/lessons', [LessonController::class, 'courseLessons']);
Route::post('courses/{course}/lessons', [LessonController::class, 'createLessonsForCourse']);
