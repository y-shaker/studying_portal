<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LessonControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_lessons_for_course()
    {
        $course = Course::factory()->has(Lesson::factory()->count(3))->create();

        $response = $this->getJson("/api/courses/{$course->id}/lessons");

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_show_returns_single_lesson()
    {
        $lesson = Lesson::factory()->create();

        $response = $this->getJson("/api/lessons/{$lesson->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $lesson->id,
                     'title' => $lesson->title,
                     'content' => $lesson->content,
                 ]);
    }

    public function test_update_modifies_existing_lesson()
    {
        $lesson = Lesson::factory()->create();

        $updateData = [
            'title' => 'Updated Lesson',
            'content' => 'Updated content',
        ];

        $response = $this->putJson("/api/lessons/{$lesson->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $lesson->id,
                     'title' => 'Updated Lesson',
                     'content' => 'Updated content',
                 ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Updated Lesson',
            'content' => 'Updated content',
        ]);
    }

    public function test_destroy_deletes_lesson()
    {
        $lesson = Lesson::factory()->create();

        $response = $this->deleteJson("/api/lessons/{$lesson->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('lessons', [
            'id' => $lesson->id,
        ]);
    }

    public function test_comments_returns_all_comments_for_lesson()
    {
        $lesson = Lesson::factory()->has(Comment::factory()->count(3))->create();

        $response = $this->getJson("/api/lessons/{$lesson->id}/comments");

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }
}