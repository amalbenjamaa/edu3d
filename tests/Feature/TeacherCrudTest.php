<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);
    }

    public function test_teacher_can_crud_course_classroom_and_slide(): void
    {
        Sanctum::actingAs($this->teacher);

        $courseRes = $this->postJson('/api/courses', [
            'title'        => 'Cours test',
            'description'  => 'Description',
            'level'        => 'beginner',
            'is_published' => true,
        ]);
        $courseRes->assertCreated();
        $courseId = $courseRes->json('course.id');
        $this->assertNotNull($courseId);

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $courseId);

        $this->putJson("/api/courses/{$courseId}", [
            'title' => 'Cours mis à jour',
        ])->assertOk();

        $classroomRes = $this->postJson('/api/classrooms', [
            'course_id' => $courseId,
            'name'      => 'Classe A',
            'capacity'  => 20,
        ]);
        $classroomRes->assertCreated();
        $classroomId = $classroomRes->json('classroom.id');
        $this->assertNotNull($classroomId);

        $this->getJson('/api/classrooms')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Classe A']);

        $slideRes = $this->postJson('/api/slides', [
            'classroom_id' => $classroomId,
            'title'        => 'Slide 1',
            'type'         => 'mixed',
            'content'      => [],
            'camera'       => ['position' => [5, 5, 5], 'target' => [0, 0, 0], 'fov' => 60],
            'duration'     => 0,
        ]);
        $slideRes->assertCreated();
        $slideId = $slideRes->json('slide.id');
        $this->assertNotNull($slideId);

        $this->getJson("/api/classrooms/{$classroomId}/slides")
            ->assertOk()
            ->assertJsonPath('data.0.id', $slideId);

        $this->putJson("/api/slides/{$slideId}", [
            'title' => 'Slide modifiée',
        ])->assertOk();

        $this->deleteJson("/api/slides/{$slideId}")->assertOk();
        $this->deleteJson("/api/classrooms/{$classroomId}")->assertOk();
        $this->deleteJson("/api/courses/{$courseId}")->assertOk();

        $this->assertDatabaseMissing('courses', ['id' => $courseId]);
    }

    public function test_student_can_read_classroom_and_slides_when_enrolled(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'teacher_id'   => $teacher->id,
            'title'        => 'Cours publié',
            'level'        => 'beginner',
            'is_published' => true,
        ]);

        $classroom = Classroom::create([
            'course_id' => $course->id,
            'name'      => 'Classe',
            'capacity'  => 10,
        ]);

        $slide = Slide::create([
            'classroom_id' => $classroom->id,
            'title'        => 'Slide étudiant',
            'type'         => 'mixed',
            'content'      => [],
            'camera'       => ['position' => [5, 5, 5], 'target' => [0, 0, 0], 'fov' => 60],
            'order'        => 1,
        ]);

        Enrollment::create([
            'user_id'      => $student->id,
            'classroom_id' => $classroom->id,
            'status'       => 'active',
            'progress'     => 0,
            'enrolled_at'  => now(),
        ]);

        Sanctum::actingAs($student);

        $this->getJson("/api/classrooms/{$classroom->id}")
            ->assertOk()
            ->assertJsonPath('classroom.id', $classroom->id);

        $this->getJson("/api/classrooms/{$classroom->id}/slides")
            ->assertOk()
            ->assertJsonPath('data.0.id', $slide->id);
    }
}
