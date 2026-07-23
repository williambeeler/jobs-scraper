<?php

namespace Tests\Feature;

use App\Models\LearningTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningTopicTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_center_lists_topics(): void
    {
        LearningTopic::query()->create([
            'title' => 'ColdFusion',
            'description' => 'Revisit the language and build a small project.',
            'status' => 'planned',
            'priority' => 'high',
        ]);

        $this->get(route('learning'))
            ->assertOk()
            ->assertSee('Topic agenda')
            ->assertSee('ColdFusion');
    }

    public function test_topic_can_be_created(): void
    {
        $this->post(route('learning-topics.store'), [
            'title' => 'Advanced React',
            'description' => 'Study rendering and composition patterns.',
            'status' => 'in_progress',
            'priority' => 'medium',
        ])->assertRedirect(route('learning'));

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

        $this->put(route('learning-topics.update', $topic), [
            'title' => 'Complex React patterns',
            'description' => null,
            'status' => 'completed',
            'priority' => 'high',
        ])->assertRedirect(route('learning'));

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

        $this->delete(route('learning-topics.destroy', $topic))
            ->assertRedirect(route('learning'));

        $this->assertDatabaseMissing('learning_topics', ['id' => $topic->id]);
    }

    public function test_topic_fields_are_validated(): void
    {
        $this->post(route('learning-topics.store'), [
            'title' => '',
            'status' => 'unknown',
            'priority' => 'urgent',
        ])->assertSessionHasErrors(['title', 'status', 'priority']);
    }
}
