<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\LearningTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $agendaId = $request->integer('agenda');
        if (! LearningTopic::query()->whereKey($agendaId)->exists()) {
            $agendaId = 0;
        }

        return view('learning.certificates.index', [
            'certificates' => Certificate::query()
                ->with('agenda')
                ->when($agendaId, fn ($query) => $query->where('learning_topic_id', $agendaId))
                ->orderBy('is_completed')
                ->orderBy('title')
                ->paginate(20)
                ->withQueryString(),
            'agendas' => $this->agendas(),
            'agendaId' => $agendaId,
        ]);
    }

    public function create(Request $request): View
    {
        return view('learning.certificates.create', [
            'certificate' => new Certificate([
                'learning_topic_id' => $request->integer('agenda') ?: null,
            ]),
            'agendas' => $this->agendas(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $certificate = Certificate::query()->create($this->validated($request));

        return to_route('learning.certificates.show', $certificate)
            ->with('status', 'Certificate added.');
    }

    public function show(Certificate $certificate): View
    {
        return view('learning.certificates.show', [
            'certificate' => $certificate->load('agenda'),
        ]);
    }

    public function edit(Certificate $certificate): View
    {
        return view('learning.certificates.edit', [
            'certificate' => $certificate,
            'agendas' => $this->agendas(),
        ]);
    }

    public function update(Request $request, Certificate $certificate): RedirectResponse
    {
        $certificate->update($this->validated($request));

        return to_route('learning.certificates.show', $certificate)
            ->with('status', 'Certificate updated.');
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $certificate->delete();

        return to_route('learning.certificates.index')
            ->with('status', 'Certificate deleted.');
    }

    /**
     * @return array{learning_topic_id: int, title: string, url: ?string, description: ?string, study_tips: ?string, completion_date: ?string, is_completed: bool}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'learning_topic_id' => ['required', 'integer', 'exists:learning_topics,id'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string'],
            'study_tips' => ['nullable', 'string'],
            'completion_date' => ['nullable', 'date'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

        foreach (['title', 'url', 'description', 'study_tips'] as $field) {
            $validated[$field] = filled($validated[$field] ?? null)
                ? trim((string) $validated[$field])
                : null;
        }

        $validated['is_completed'] = $request->boolean('is_completed');

        return $validated;
    }

    /** @return Collection<int, LearningTopic> */
    private function agendas(): Collection
    {
        return LearningTopic::query()->orderBy('title')->get(['id', 'title']);
    }
}
