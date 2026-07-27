<?php

namespace WilliamBeeler\Crm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use WilliamBeeler\Crm\Models\Relationship;
use WilliamBeeler\Crm\Models\Status;

class StatusController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $statusQuery = Status::query();
        $relationshipQuery = Relationship::query();

        if ($search = trim((string) $request->input('search_term', ''))) {
            $statusQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%");
            });

            $relationshipQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $statuses = $statusQuery
            ->orderBy('id')
            ->paginate(25, ['*'], 'statuses_page')
            ->withQueryString();

        $relationships = $relationshipQuery
            ->orderBy('id')
            ->paginate(25, ['*'], 'relationships_page')
            ->withQueryString();

        return Inertia::render('Statuses/Index', [
            'filters' => [
                'search_term' => (string) $request->input('search_term', ''),
            ],
            'statuses' => [
                'data' => $statuses->getCollection()
                    ->map(fn (Status $status): array => [
                        'id' => $status->id,
                        'name' => $status->name,
                        'slug' => $status->slug,
                        'description' => $status->description,
                        'color' => $status->color,
                        'is_default' => (bool) $status->is_default,
                        'created_at_label' => $status->created_at?->format('M j, Y'),
                        'show_url' => route('statuses.show', $status),
                    ])
                    ->values()
                    ->all(),
                'meta' => [
                    'current_page' => $statuses->currentPage(),
                    'last_page' => $statuses->lastPage(),
                    'per_page' => $statuses->perPage(),
                    'total' => $statuses->total(),
                    'from' => $statuses->firstItem(),
                    'to' => $statuses->lastItem(),
                ],
                'links' => $statuses->linkCollection()->toArray(),
            ],
            'relationships' => [
                'data' => $relationships->getCollection()
                    ->map(fn (Relationship $relationship): array => [
                        'id' => $relationship->id,
                        'name' => $relationship->name,
                        'slug' => $relationship->slug,
                        'description' => $relationship->description,
                        'created_at_label' => $relationship->created_at?->format('M j, Y'),
                    ])
                    ->values()
                    ->all(),
                'meta' => [
                    'current_page' => $relationships->currentPage(),
                    'last_page' => $relationships->lastPage(),
                    'per_page' => $relationships->perPage(),
                    'total' => $relationships->total(),
                    'from' => $relationships->firstItem(),
                    'to' => $relationships->lastItem(),
                ],
                'links' => $relationships->linkCollection()->toArray(),
            ],
        ]);
    }

    public function create()
    {
        return redirect()->route('statuses.index')->with('success', 'Statuses are system-defined and cannot be edited.');
    }

    public function store(Request $request)
    {
        return redirect()->route('statuses.index')->with('success', 'Statuses are system-defined and cannot be edited.');
    }

    public function show(Status $status): InertiaResponse
    {
        return Inertia::render('Statuses/Show', [
            'status' => [
                'id' => $status->id,
                'name' => $status->name,
                'slug' => $status->slug,
                'description' => $status->description,
                'color' => $status->color,
                'is_default' => (bool) $status->is_default,
            ],
        ]);
    }

    public function edit(Status $status)
    {
        return redirect()->route('statuses.show', $status)->with('success', 'Statuses are system-defined and cannot be edited.');
    }

    public function update(Request $request, Status $status)
    {
        return redirect()->route('statuses.show', $status)->with('success', 'Statuses are system-defined and cannot be edited.');
    }

    public function destroy(Status $status)
    {
        return redirect()->route('statuses.index')->with('success', 'Statuses are system-defined and cannot be edited.');
    }
}
