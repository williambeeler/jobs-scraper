<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\LearningTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificates_can_be_listed_and_filtered_by_agenda(): void
    {
        $agenda = $this->agenda('Laravel');
        $otherAgenda = $this->agenda('React');
        Certificate::query()->create([
            'learning_topic_id' => $agenda->id,
            'title' => 'Laravel Certification',
        ]);
        Certificate::query()->create([
            'learning_topic_id' => $otherAgenda->id,
            'title' => 'React Certification',
        ]);

        $this->get(route('learning.certificates.index', ['agenda' => $agenda->id]))
            ->assertOk()
            ->assertSee('Laravel Certification')
            ->assertDontSee('React Certification');
    }

    public function test_certificate_can_be_created_for_an_agenda(): void
    {
        $agenda = $this->agenda();

        $response = $this->post(route('learning.certificates.store'), [
            'learning_topic_id' => $agenda->id,
            'title' => 'AWS Developer Associate',
            'url' => 'https://example.com/aws-certificate',
            'description' => 'Prepare for the associate exam.',
            'study_tips' => 'Complete practice exams every week.',
            'completion_date' => '2026-10-15',
            'is_completed' => '1',
        ]);

        $certificate = Certificate::query()->firstOrFail();
        $response->assertRedirect(route('learning.certificates.show', $certificate));
        $this->assertDatabaseHas('certificates', [
            'learning_topic_id' => $agenda->id,
            'title' => 'AWS Developer Associate',
            'is_completed' => true,
        ]);
        $this->assertTrue($agenda->certificates->contains($certificate));
        $this->assertTrue($certificate->agenda->is($agenda));
    }

    public function test_certificate_show_and_edit_views_are_available(): void
    {
        $certificate = Certificate::query()->create([
            'learning_topic_id' => $this->agenda()->id,
            'title' => 'PHP Certification',
            'description' => 'Certification description.',
        ]);

        $this->get(route('learning.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('PHP Certification')
            ->assertSee('Certification description.');

        $this->get(route('learning.certificates.edit', $certificate))
            ->assertOk()
            ->assertSee('Edit certificate')
            ->assertSee('PHP Certification');
    }

    public function test_certificate_can_be_updated(): void
    {
        $certificate = Certificate::query()->create([
            'learning_topic_id' => $this->agenda()->id,
            'title' => 'Original title',
        ]);

        $this->put(route('learning.certificates.update', $certificate), [
            'learning_topic_id' => $certificate->learning_topic_id,
            'title' => 'Updated title',
            'is_completed' => '1',
            'completion_date' => '2026-12-01',
        ])->assertRedirect(route('learning.certificates.show', $certificate));

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'title' => 'Updated title',
            'is_completed' => true,
            'completion_date' => '2026-12-01 00:00:00',
        ]);
    }

    public function test_certificate_can_be_deleted(): void
    {
        $certificate = Certificate::query()->create([
            'learning_topic_id' => $this->agenda()->id,
            'title' => 'Delete me',
        ]);

        $this->delete(route('learning.certificates.destroy', $certificate))
            ->assertRedirect(route('learning.certificates.index'));

        $this->assertDatabaseMissing('certificates', ['id' => $certificate->id]);
    }

    public function test_certificate_fields_are_validated(): void
    {
        $this->post(route('learning.certificates.store'), [
            'learning_topic_id' => 999999,
            'title' => '',
            'url' => 'not-a-url',
            'completion_date' => 'not-a-date',
        ])->assertSessionHasErrors(['learning_topic_id', 'title', 'url', 'completion_date']);
    }

    private function agenda(string $title = 'Cloud computing'): LearningTopic
    {
        return LearningTopic::query()->create([
            'title' => $title,
            'status' => 'planned',
            'priority' => 'medium',
        ]);
    }
}
