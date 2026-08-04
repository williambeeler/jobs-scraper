<?php

namespace App\Http\Controllers;

use App\Models\LearningTopic;
use App\Models\PortfolioProject;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortfolioProjectController extends Controller
{
    public function index(Request $request): View
    {
        $agendaId = $request->integer('agenda');
        if (! LearningTopic::query()->whereKey($agendaId)->exists()) {
            $agendaId = 0;
        }

        return view('learning.projects.index', [
            'projects' => PortfolioProject::query()
                ->with(['agenda', 'learningModules'])
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
        return view('learning.projects.create', [
            'project' => new PortfolioProject(['learning_topic_id' => $request->integer('agenda') ?: null]),
            'agendas' => $this->agendas(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = PortfolioProject::query()->create($this->validated($request));

        return to_route('learning.projects.show', $project)->with('status', 'Portfolio project added.');
    }

    public function show(PortfolioProject $project): View
    {
        return view('learning.projects.show', [
            'project' => $project->load(['agenda', 'learningModules']),
        ]);
    }

    public function edit(PortfolioProject $project): View
    {
        return view('learning.projects.edit', [
            'project' => $project,
            'agendas' => $this->agendas(),
        ]);
    }

    public function update(Request $request, PortfolioProject $project): RedirectResponse
    {
        $project->update($this->validated($request));

        return to_route('learning.projects.show', $project)->with('status', 'Portfolio project updated.');
    }

    public function destroy(PortfolioProject $project): RedirectResponse
    {
        $project->delete();

        return to_route('learning.projects.index')->with('status', 'Portfolio project deleted.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'learning_topic_id' => ['required', 'integer', 'exists:learning_topics,id'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string'],
            'completion_date' => ['nullable', 'date'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

        foreach (['title', 'url', 'description'] as $field) {
            $validated[$field] = filled($validated[$field] ?? null) ? trim((string) $validated[$field]) : null;
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
