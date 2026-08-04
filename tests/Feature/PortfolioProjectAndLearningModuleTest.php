<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\LearningModule;
use App\Models\LearningTopic;
use App\Models\PortfolioProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioProjectAndLearningModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_project_crud(): void
    {
        $agenda = $this->agenda();
        $this->post(route('learning.projects.store'), [
            'learning_topic_id' => $agenda->id,
            'title' => 'Learning dashboard',
            'url' => 'https://example.com/project',
            'description' => 'Build a progress dashboard.',
        ])->assertRedirect();

        $project = PortfolioProject::query()->firstOrFail();
        $this->get(route('learning.projects.show', $project))->assertOk()->assertSee('Learning dashboard');
        $this->put(route('learning.projects.update', $project), [
            'learning_topic_id' => $agenda->id,
            'title' => 'Completed dashboard',
            'is_completed' => '1',
            'completion_date' => '2026-12-20',
        ])->assertRedirect(route('learning.projects.show', $project));
        $this->assertDatabaseHas('portfolio_projects', ['id' => $project->id, 'is_completed' => true]);
        $this->delete(route('learning.projects.destroy', $project))->assertRedirect(route('learning.projects.index'));
        $this->assertDatabaseMissing('portfolio_projects', ['id' => $project->id]);
    }

    public function test_modules_are_polymorphic_and_calculate_progress(): void
    {
        $agenda = $this->agenda();
        $certificate = Certificate::query()->create(['learning_topic_id' => $agenda->id, 'title' => 'AWS']);
        $project = PortfolioProject::query()->create(['learning_topic_id' => $agenda->id, 'title' => 'Cloud app']);

        foreach (range(1, 10) as $number) {
            $certificate->learningModules()->create([
                'title' => "Module {$number}",
                'is_completed' => $number <= 5,
            ]);
        }
        $project->learningModules()->create(['title' => 'Project module']);

        $this->assertSame(50, $certificate->fresh()->progressPercentage());
        $this->assertInstanceOf(Certificate::class, $certificate->learningModules->first()->moduleable);
        $this->assertInstanceOf(PortfolioProject::class, $project->learningModules->first()->moduleable);
        $this->get(route('learning.certificates.show', $certificate))->assertOk()->assertSee('50%');
    }

    public function test_learning_module_can_be_created_updated_and_deleted(): void
    {
        $certificate = Certificate::query()->create([
            'learning_topic_id' => $this->agenda()->id,
            'title' => 'PHP',
        ]);

        $this->post(route('learning.certificates.modules.store', $certificate), [
            'title' => 'Language basics',
            'url' => 'https://example.com/module',
            'description' => 'Review syntax.',
        ])->assertRedirect(route('learning.certificates.show', $certificate));

        $module = LearningModule::query()->firstOrFail();
        $this->put(route('learning.modules.update', $module), [
            'title' => 'Language fundamentals',
            'is_completed' => '1',
            'completion_date' => '2026-11-01',
        ])->assertRedirect(route('learning.certificates.show', $certificate));
        $this->assertDatabaseHas('learning_modules', ['id' => $module->id, 'is_completed' => true]);
        $this->delete(route('learning.modules.destroy', $module))
            ->assertRedirect(route('learning.certificates.show', $certificate));
        $this->assertDatabaseMissing('learning_modules', ['id' => $module->id]);
    }

    private function agenda(): LearningTopic
    {
        return LearningTopic::query()->create([
            'title' => 'Web development',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }
}
