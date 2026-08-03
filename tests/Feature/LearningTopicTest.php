<?php

namespace Tests\Feature;

use App\Models\LearningTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningTopicTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_dashboard_links_to_the_agenda(): void
    {
        $this->get(route('learning'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee(route('learning.agenda'));
    }

    public function test_learning_agenda_lists_topics(): void
    {
        LearningTopic::query()->create([
            'title' => 'ColdFusion',
            'description' => 'Revisit the language and build a small project.',
            'status' => 'planned',
            'priority' => 'high',
        ]);

        $this->get(route('learning.agenda'))
            ->assertOk()
            ->assertSee('Topic agenda')
            ->assertSee('ColdFusion');
    }

    public function test_topic_can_be_created(): void
    {
        $this->post(route('learning.agenda.topics.store'), [
            'title' => 'Advanced React',
            'description' => 'Study rendering and composition patterns.',
            'status' => 'in_progress',
            'priority' => 'medium',
        ])->assertRedirect(route('learning.agenda'));

        $this->assertDatabaseHas('learning_topics', [
            'title' => 'Advanced React',
            'status' => 'in_progress',
        ]);
    }

    public function test_topic_can_be_updated(): void
    {
        $topic = LearningTopic::query()->create([
            'title' => 'React',
            'status' => 'planned',
            'priority' => 'medium',
        ]);

        $this->put(route('learning.agenda.topics.update', $topic), [
            'title' => 'Complex React patterns',
            'description' => null,
            'status' => 'completed',
            'priority' => 'high',
        ])->assertRedirect(route('learning.agenda'));

        $this->assertDatabaseHas('learning_topics', [
            'id' => $topic->id,
            'title' => 'Complex React patterns',
            'status' => 'completed',
        ]);
    }

    public function test_topic_can_be_deleted(): void
    {
        $topic = LearningTopic::query()->create([
            'title' => 'ColdFusion',
            'status' => 'planned',
            'priority' => 'low',
        ]);

        $this->delete(route('learning.agenda.topics.destroy', $topic))
            ->assertRedirect(route('learning.agenda'));

        $this->assertDatabaseMissing('learning_topics', ['id' => $topic->id]);
    }

    public function test_topic_fields_are_validated(): void
    {
        $this->post(route('learning.agenda.topics.store'), [
            'title' => '',
            'status' => 'unknown',
            'priority' => 'urgent',
        ])->assertSessionHasErrors(['title', 'status', 'priority']);
    }
}
