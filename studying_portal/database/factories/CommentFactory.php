<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraph,
            'user_id' => User::factory(), // Assuming each comment belongs to a user
            'lesson_id' => Lesson::factory(), // Assuming each comment belongs to a lesson
        ];
    }
}