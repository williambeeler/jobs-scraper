<?php

namespace Tests\Feature;

use App\Models\CareerPath;
use App\Models\CareerPathFile;
use App\Models\LearningTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CareerPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_career_path_crud(): void
    {
        $this->post(route('learning.career-paths.store'), [
            'title' => 'App Developer',
            'description' => '<p>Skills for <strong>building applications</strong>.</p>',
        ])->assertRedirect();

        $careerPath = CareerPath::query()->firstOrFail();
        $this->get(route('learning.career-paths.show', $careerPath))
            ->assertOk()
            ->assertSee('<strong>building applications</strong>', false);

        $this->put(route('learning.career-paths.update', $careerPath), [
            'title' => 'Application Developer',
        ])->assertRedirect(route('learning.career-paths.show', $careerPath));

        $this->assertDatabaseHas('career_paths', ['id' => $careerPath->id, 'title' => 'Application Developer']);
        $this->delete(route('learning.career-paths.destroy', $careerPath))
            ->assertRedirect(route('learning.career-paths.index'));
        $this->assertDatabaseMissing('career_paths', ['id' => $careerPath->id]);
    }

    public function test_agenda_can_belong_to_multiple_career_paths(): void
    {
        $appDeveloper = CareerPath::query()->create(['title' => 'App Developer']);
        $wordpressEngineer = CareerPath::query()->create(['title' => 'WordPress Engineer']);

        $this->post(route('learning.agenda.topics.store'), [
            'title' => 'Web Development',
            'status' => 'planned',
            'priority' => 'high',
            'career_path_ids' => [$appDeveloper->id, $wordpressEngineer->id],
        ])->assertRedirect(route('learning.agenda'));

        $agenda = LearningTopic::query()->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$appDeveloper->id, $wordpressEngineer->id],
            $agenda->careerPaths()->pluck('career_paths.id')->all(),
        );
        $this->get(route('learning.career-paths.show', $appDeveloper))->assertOk()->assertSee('Web Development');
    }

    public function test_updating_an_agenda_syncs_its_career_paths(): void
    {
        $first = CareerPath::query()->create(['title' => 'First Path']);
        $second = CareerPath::query()->create(['title' => 'Second Path']);
        $agenda = LearningTopic::query()->create(['title' => 'Testing', 'status' => 'planned', 'priority' => 'medium']);
        $agenda->careerPaths()->attach($first);

        $this->put(route('learning.agenda.topics.update', $agenda), [
            'title' => 'Testing',
            'status' => 'in_progress',
            'priority' => 'medium',
            'career_path_ids' => [$second->id],
        ])->assertRedirect(route('learning.agenda'));

        $this->assertEquals([$second->id], $agenda->careerPaths()->pluck('career_paths.id')->all());
    }

    public function test_deleting_a_career_path_does_not_delete_its_agendas(): void
    {
        $careerPath = CareerPath::query()->create(['title' => 'Temporary Path']);
        $agenda = LearningTopic::query()->create(['title' => 'Keep Me', 'status' => 'planned', 'priority' => 'low']);
        $careerPath->agendas()->attach($agenda);

        $careerPath->delete();

        $this->assertDatabaseHas('learning_topics', ['id' => $agenda->id]);
        $this->assertDatabaseMissing('career_path_learning_topic', ['career_path_id' => $careerPath->id]);
    }

    public function test_files_can_be_uploaded_viewed_downloaded_and_deleted(): void
    {
        Storage::fake('local');

        $this->post(route('learning.career-paths.store'), [
            'title' => 'Designer',
            'files' => [UploadedFile::fake()->image('interface.png', 800, 600)],
        ])->assertRedirect();

        $careerPath = CareerPath::query()->firstOrFail();
        $file = CareerPathFile::query()->firstOrFail();
        Storage::disk('local')->assertExists($file->path);

        $this->get(route('learning.career-paths.show', $careerPath))
            ->assertOk()
            ->assertSee('interface.png');
        $this->get(route('learning.career-paths.files.show', [$careerPath, $file]))->assertOk();
        $this->get(route('learning.career-paths.files.download', [$careerPath, $file]))
            ->assertOk()
            ->assertDownload('interface.png');

        $this->delete(route('learning.career-paths.files.destroy', [$careerPath, $file]))->assertRedirect();
        $this->assertDatabaseMissing('career_path_files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_deleting_a_career_path_removes_its_stored_files(): void
    {
        Storage::fake('local');
        $careerPath = CareerPath::query()->create(['title' => 'Temporary Files']);
        $path = UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf')
            ->store("career-paths/{$careerPath->id}", 'local');
        $careerPath->files()->create([
            'original_name' => 'guide.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 102400,
        ]);

        $careerPath->delete();

        Storage::disk('local')->assertMissing($path);
    }
}
