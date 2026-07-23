<?php

namespace App\Http\Controllers;

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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LearningTopic::query()->create($this->validated($request));

        return to_route('learning')->with('status', 'Learning topic added.');
    }

    public function edit(LearningTopic $learningTopic): View
    {
        return view('learning.edit', [
            'topic' => $learningTopic,
            'statuses' => LearningTopic::STATUSES,
            'priorities' => LearningTopic::PRIORITIES,
        ]);
    }

    public function update(Request $request, LearningTopic $learningTopic): RedirectResponse
    {
        $learningTopic->update($this->validated($request));

        return to_route('learning')->with('status', 'Learning topic updated.');
    }

    public function destroy(LearningTopic $learningTopic): RedirectResponse
    {
        $learningTopic->delete();

        return to_route('learning')->with('status', 'Learning topic deleted.');
    }

    /**
     * @return array{title: string, description: ?string, status: string, priority: string}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(LearningTopic::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(LearningTopic::PRIORITIES))],
        ]);

        $validated['title'] = trim($validated['title']);
        $validated['description'] = filled($validated['description'] ?? null)
            ? trim($validated['description'])
            : null;

        return $validated;
    }
}
