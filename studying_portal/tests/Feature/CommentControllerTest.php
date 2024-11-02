<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_comments_for_lesson()
    {
        $lesson = Lesson::factory()->create();
        $comments = Comment::factory()->count(3)->create(['lesson_id' => $lesson->id]);

        $response = $this->getJson("/api/lessons/{$lesson->id}/comments");

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_store_creates_new_comment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $user->courses()->attach($course->id);

        $commentData = [
            'content' => 'This is a comment.',
        ];

        $response = $this->postJson("/api/users/{$user->id}/lessons/{$lesson->id}/comments", $commentData);

        $response->assertStatus(201)
                 ->assertJson([
                     'content' => 'This is a comment.',
                 ]);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a comment.',
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_store_fails_if_user_not_enrolled_in_course()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $commentData = [
            'content' => 'This is a comment.',
        ];

        $response = $this->postJson("/api/users/{$user->id}/lessons/{$lesson->id}/comments", $commentData);

        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'User is not enrolled in the course associated with this lesson.',
                 ]);

        $this->assertDatabaseMissing('comments', [
            'content' => 'This is a comment',
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_store_fails_if_user_already_commented_on_lesson()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $user->courses()->attach($course->id);
        $comment = Comment::factory()->create(['user_id' => $user->id, 'lesson_id' => $lesson->id]);

        $commentData = [
            'content' => 'This is another comment.',
        ];

        $response = $this->postJson("/api/users/{$user->id}/lessons/{$lesson->id}/comments", $commentData);

        $response->assertStatus(409)
                 ->assertJson([
                     'message' => 'User has already commented on this lesson.',
                 ]);

        $this->assertDatabaseMissing('comments', [
            'content' => 'This is another comment',
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_show_returns_single_comment()
    {
        $comment = Comment::factory()->create();

        $response = $this->getJson("/api/comments/{$comment->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $comment->id,
                     'content' => $comment->content,
                 ]);
    }

    public function test_update_modifies_existing_comment()
    {
        $comment = Comment::factory()->create();

        $updateData = [
            'content' => 'Updated comment content.',
        ];

        $response = $this->putJson("/api/comments/{$comment->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $comment->id,
                     'content' => 'Updated comment content.',
                 ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated comment content.',
        ]);
    }

    public function test_destroy_deletes_comment()
    {
        $comment = Comment::factory()->create();

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }
}