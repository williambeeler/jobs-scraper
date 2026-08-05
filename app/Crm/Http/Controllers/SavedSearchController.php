<?php

namespace App\Crm\Http\Controllers;

use App\Crm\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class SavedSearchController extends Controller
{
    public function index()
    {
        $savedSearches = SavedSearch::query()
            ->where('resource', 'companies')
            ->latest()
            ->paginate(25);

        return view('crm.saved-searches.index', [
            'savedSearches' => [
                'data' => $savedSearches->getCollection()
                    ->map(fn (SavedSearch $savedSearch): array => [
                        'id' => $savedSearch->id,
                        'name' => $savedSearch->name,
                        'parameters' => $savedSearch->parameters ?? [],
                        'created_at_label' => $savedSearch->created_at?->format('M j, Y g:i A'),
                        'open_url' => route('crm.companies.index', $savedSearch->parameters ?? []),
                    ])
                    ->values()
                    ->all(),
                'meta' => [
                    'current_page' => $savedSearches->currentPage(),
                    'last_page' => $savedSearches->lastPage(),
                    'per_page' => $savedSearches->perPage(),
                    'total' => $savedSearches->total(),
                    'from' => $savedSearches->firstItem(),
                    'to' => $savedSearches->lastItem(),
                ],
                'links' => $savedSearches->linkCollection()->toArray(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'resource' => ['nullable', 'string', 'in:companies'],
            'search_term' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer'],
            'tag_filter' => ['nullable', 'string'],
            'campaign_filter' => ['nullable', 'string'],
            'created_range' => ['nullable', 'string'],
        ]);

        $resource = $validated['resource'] ?? 'companies';
        $parameters = $this->normalizedParameters($validated);

        if (empty($parameters)) {
            return response()->json([
                'saved' => false,
                'message' => 'No search filters to save.',
            ], 422);
        }

        $parameterHash = hash('sha256', json_encode($parameters));
        $savedSearch = SavedSearch::firstOrCreate(
            [
                'resource' => $resource,
                'parameter_hash' => $parameterHash,
            ],
            [
                'name' => $this->buildSearchName($parameters),
                'parameters' => $parameters,
            ]
        );

        return response()->json([
            'saved' => true,
            'already_saved' => ! $savedSearch->wasRecentlyCreated,
            'id' => $savedSearch->id,
            'name' => $savedSearch->name,
        ]);
    }

    private function normalizedParameters(array $raw): array
    {
        $parameters = Arr::only($raw, ['search_term', 'status_id', 'tag_filter', 'campaign_filter']);

        $parameters = array_filter($parameters, function ($value) {
            return ! is_null($value) && $value !== '';
        });

        ksort($parameters);

        return $parameters;
    }

    private function buildSearchName(array $parameters): string
    {
        $parts = [];
        if (! empty($parameters['search_term'])) {
            $parts[] = 'Search: '.$parameters['search_term'];
        }
        if (! empty($parameters['status_id'])) {
            $parts[] = 'Status ID: '.$parameters['status_id'];
        }
        if (! empty($parameters['tag_filter'])) {
            $parts[] = 'Tag: '.$parameters['tag_filter'];
        }
        if (! empty($parameters['campaign_filter'])) {
            $parts[] = 'Campaign: '.$parameters['campaign_filter'];
        }

        if (empty($parts)) {
            return 'Saved Company Search';
        }

        return implode(' | ', $parts);
    }
}
