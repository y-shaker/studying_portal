<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Comment;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_users()
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_show_returns_single_user()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $user->id,
                     'name' => $user->name,
                     'email' => $user->email,
                 ]);
    }

    public function test_store_creates_new_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'securepassword',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
                 ->assertJson([
                     'name' => 'John Doe',
                     'email' => 'john.doe@example.com',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
        ]);
    }

    public function test_store_returns_validation_error_for_duplicate_email()
    {
        User::factory()->create(['email' => 'john.doe@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'securepassword',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('email');
    }

    public function test_update_modifies_existing_user()
    {
        $user = User::factory()->create();

        $updateData = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
        ];

        $response = $this->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $user->id,
                     'name' => 'Jane Doe',
                     'email' => 'jane.doe@example.com',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
        ]);
    }

    public function test_destroy_deletes_user()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_userBadge_returns_correct_badge()
    {
        $user = User::factory()->create(['achievements_count' => 7]);

        $response = $this->getJson("/api/users/{$user->id}/badge");

        $response->assertStatus(200)
                 ->assertJson([
                     'current_badge' => 'Intermediate',
                     'next_badge' => 'Advanced',
                     'remaining_to_unlock_next_badge' => 1,
                 ]);
    }

    public function test_userBadge_returns_correct_badge_with_no_certificates()
    {
        $user = User::factory()->create(['achievements_count' => 0]);

        $response = $this->getJson("/api/users/{$user->id}/badge");

        $response->assertStatus(200)
                 ->assertJson([
                     'current_badge' => 'Beginner',
                     'next_badge' => 'Intermediate',
                     'remaining_to_unlock_next_badge' => 4,
                 ]);
    }

    public function test_userBadge_returns_correct_badge_with_all_certificates()
    {
        $user = User::factory()->create(['achievements_count' => 10]);

        $response = $this->getJson("/api/users/{$user->id}/badge");

        $response->assertStatus(200)
                 ->assertJson([
                     'current_badge' => 'Master',
                     'next_badge' => '',
                     'remaining_to_unlock_next_badge' => 0,
                 ]);
    }

    public function test_enrollCourse_enrolls_user_in_course()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $response = $this->postJson("/api/users/{$user->id}/courses/{$course->id}");

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_courses', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_add_listenLesson_to_user()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $user->courses()->attach($course->id); // Enroll the user in the course

        $response = $this->postJson("/api/users/{$user->id}/lessons/{$lesson->id}");

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_lessons', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_listenLesson_fails_if_user_not_enrolled_in_course()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        // Do not enroll the user in the course

        $response = $this->postJson("/api/users/{$user->id}/lessons/{$lesson->id}");

        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'User is not enrolled in the course associated with this lesson.',
                 ]);

        $this->assertDatabaseMissing('user_lessons', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_listenLesson_fails_if_user_already_listened_to_lesson()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $user->courses()->attach($course->id); // Enroll the user in the course
        $user->lessons()->attach($lesson->id); // User has already listened to the lesson

        $response = $this->postJson("/api/users/{$user->id}/lessons/{$lesson->id}");

        $response->assertStatus(409)
                 ->assertJson([
                     'message' => 'User has already listened to this lesson.',
                 ]);

        // Ensure the lesson is not added again
        $this->assertDatabaseCount('user_lessons', 1);
    }

    public function test_calculateAchievements_with_no_achievements()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertStatus(200)
                 ->assertJson([
                     'unlocked_achievements' => [],
                     'next_available_achievements' => ['First Lesson Watched', 'First Comment Written'],
                 ]);
    }

    public function test_calculateAchievements_with_only_lessons_watched()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lessons = Lesson::factory()->count(5)->create(['course_id' => $course->id]);

        $user->courses()->attach($course->id);
        $user->lessons()->attach($lessons->pluck('id')->toArray());

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertStatus(200)
                 ->assertJson([
                     'unlocked_achievements' => [
                         'First Lesson Watched',
                         '5 Lessons Watched',
                     ],
                     'next_available_achievements' => ['10 Lessons Watched', 'First Comment Written'],
                 ]);
    }

    public function test_calculateAchievements_with_only_comments_written()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);
        Comment::factory()->count(2)->create(['user_id' => $user->id, 'lesson_id' => $lesson->id]);
        $user->courses()->attach($course->id);

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertStatus(200)
                 ->assertJson([
                     'unlocked_achievements' => [
                         'First Comment Written',
                     ],
                     'next_available_achievements' => ['First Lesson Watched', '3 Comments Written'],
                 ]);
    }


    public function test_calculateAchievements_with_comments_written_and_lesson_watched()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);
        $comments = Comment::factory()->count(3)->create(['user_id' => $user->id, 'lesson_id' => $lesson->id]);

        $user->courses()->attach($course->id);
        $user->lessons()->attach($lesson->id);

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertStatus(200)
                 ->assertJson([
                     'unlocked_achievements' => [
                         'First Lesson Watched',
                         'First Comment Written',
                         '3 Comments Written',
                     ],
                     'next_available_achievements' => ['5 Lessons Watched', '5 Comments Written'],
                 ]);
    }

    public function test_calculateAchievements_with_all_achievements()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lessons = Lesson::factory()->count(50)->create(['course_id' => $course->id]);
        $comments = Comment::factory()->count(20)->create(['user_id' => $user->id, 'lesson_id' => $lessons->first()->id]);

        $user->courses()->attach($course->id);
        $user->lessons()->attach($lessons->pluck('id')->toArray());

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertStatus(200)
                 ->assertJson([
                     'unlocked_achievements' => [
                         'First Lesson Watched',
                         '5 Lessons Watched',
                         '10 Lessons Watched',
                         '25 Lessons Watched',
                         '50 Lessons Watched',
                         'First Comment Written',
                         '3 Comments Written',
                         '5 Comments Written',
                         '10 Comments Written',
                         '20 Comments Written',
                     ],
                     'next_available_achievements' => [],
                 ]);
    }
}