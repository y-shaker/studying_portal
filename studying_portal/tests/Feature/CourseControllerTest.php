<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_courses_with_lessons()
    {
        Course::factory()->has(Lesson::factory()->count(3))->count(2)->create();

        $response = $this->getJson('/api/courses');

        $response->assertStatus(200)
                 ->assertJsonCount(2)
                 ->assertJsonStructure([
                     '*' => [
                         'id',
                         'title',
                         'description',
                         'lessons' => [
                             '*' => [
                                 'id',
                                 'title',
                                 'content',
                             ],
                         ],
                     ],
                 ]);
    }

    public function test_show_returns_single_course_with_lessons()
    {
        $course = Course::factory()->has(Lesson::factory()->count(3))->create();

        $response = $this->getJson("/api/courses/{$course->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $course->id,
                     'title' => $course->title,
                     'description' => $course->description,
                     'lessons' => [
                         ['id' => $course->lessons[0]->id],
                         ['id' => $course->lessons[1]->id],
                         ['id' => $course->lessons[2]->id],
                     ],
                 ]);
    }

    public function test_store_creates_new_course_with_lessons()
    {
        $courseData = [
            'title' => 'New Course',
            'description' => 'Course description',
            'lessons' => [
                ['title' => 'Lesson 1', 'content' => 'Content 1'],
                ['title' => 'Lesson 2', 'content' => 'Content 2'],
            ],
        ];

        $response = $this->postJson('/api/courses', $courseData);

        $response->assertStatus(201)
                 ->assertJson([
                     'title' => 'New Course',
                     'description' => 'Course description',
                     'lessons' => [
                         ['title' => 'Lesson 1', 'content' => 'Content 1'],
                         ['title' => 'Lesson 2', 'content' => 'Content 2'],
                     ],
                 ]);

        $this->assertDatabaseHas('courses', [
            'title' => 'New Course',
            'description' => 'Course description',
        ]);

        $this->assertDatabaseHas('lessons', [
            'title' => 'Lesson 1',
            'content' => 'Content 1',
        ]);

        $this->assertDatabaseHas('lessons', [
            'title' => 'Lesson 2',
            'content' => 'Content 2',
        ]);
    }

    public function test_update_modifies_existing_course_and_lessons()
    {
        $course = Course::factory()->has(Lesson::factory()->count(2))->create();

        $updateData = [
            'title' => 'Updated Course',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/courses/{$course->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'title' => 'Updated Course',
                     'description' => 'Updated description',
                 ]);
    }

    public function test_destroy_deletes_course_and_lessons()
    {
        $course = Course::factory()->has(Lesson::factory()->count(2))->create();
        $lesson = $course->lessons->first();
        $lesson_2 = $course->lessons->last();

        $response = $this->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);

        $this->assertDatabaseMissing('lessons', [
            'id' => $lesson->id,
        ]);

        $this->assertDatabaseMissing('lessons', [
            'id' => $lesson_2->id,
        ]);
    }
}