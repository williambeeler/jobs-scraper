<?php

namespace App\Http\Controllers;

use App\Models\CareerPath;
use App\Models\LearningTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LearningTopicController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        if (! array_key_exists($status, LearningTopic::STATUSES)) {
            $status = '';
        }

        return view('learning.index', [
            'topics' => LearningTopic::query()
                ->with('careerPaths')
                ->withCount(['certificates', 'portfolioProjects'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderByRaw("case priority when 'high' then 1 when 'medium' then 2 else 3 end")
                ->orderBy('title')
                ->get(),
            'status' => $status,
            'statuses' => LearningTopic::STATUSES,
            'priorities' => LearningTopic::PRIORITIES,
            'topicCounts' => LearningTopic::query()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
            'careerPaths' => CareerPath::query()->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $careerPathIds = $validated['career_path_ids'];
        unset($validated['career_path_ids']);

        $topic = LearningTopic::query()->create($validated);
        $topic->careerPaths()->sync($careerPathIds);

        return to_route('learning.agenda')->with('status', 'Learning topic added.');
    }

    public function edit(LearningTopic $learningTopic): View
    {
        return view('learning.edit', [
            'topic' => $learningTopic,
            'statuses' => LearningTopic::STATUSES,
            'priorities' => LearningTopic::PRIORITIES,
            'careerPaths' => CareerPath::query()->orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, LearningTopic $learningTopic): RedirectResponse
    {
        $validated = $this->validated($request);
        $careerPathIds = $validated['career_path_ids'];
        unset($validated['career_path_ids']);

        $learningTopic->update($validated);
        $learningTopic->careerPaths()->sync($careerPathIds);

        return to_route('learning.agenda')->with('status', 'Learning topic updated.');
    }

    public function destroy(LearningTopic $learningTopic): RedirectResponse
    {
        $learningTopic->delete();

        return to_route('learning.agenda')->with('status', 'Learning topic deleted.');
    }

    /**
     * @return array{title: string, description: ?string, status: string, priority: string, career_path_ids: array<int, int>}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(LearningTopic::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(LearningTopic::PRIORITIES))],
            'career_path_ids' => ['nullable', 'array'],
            'career_path_ids.*' => ['integer', 'distinct', 'exists:career_paths,id'],
        ]);

        $validated['title'] = trim($validated['title']);
        $validated['description'] = filled($validated['description'] ?? null)
            ? trim($validated['description'])
            : null;
        $validated['career_path_ids'] = array_map('intval', $validated['career_path_ids'] ?? []);

        return $validated;
    }
}
