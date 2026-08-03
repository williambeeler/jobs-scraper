<?php

namespace WilliamBeeler\Crm\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Note;
use WilliamBeeler\Crm\Models\Relationship;
use WilliamBeeler\Crm\Models\SavedSearch;
use WilliamBeeler\Crm\Models\Status;
use WilliamBeeler\Crm\Models\Tag;
use WilliamBeeler\Crm\Services\Companies\CompanyAiEnrichmentService;
use WilliamBeeler\Crm\Services\Companies\CompanyOpportunityScoringService;
use WilliamBeeler\Crm\Services\GooglePlacesUsageDashboardService;
use WilliamBeeler\Crm\Support\Avery94200Pdf;
use WilliamBeeler\Crm\Support\CompanyListPdf;
use WilliamBeeler\Crm\Support\TableNames;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $statuses = Status::orderBy('name')->get();
        $tags = Tag::where('type', 'tag')->orderBy('name')->get();
        $campaigns = Tag::where('type', 'campaign')->orderBy('name')->get();
        $currentFilterParameters = $this->normalizedSearchParameters([
            'search' => trim((string) $request->input('search', $request->input('search_term', ''))),
            'status_id' => $request->input('status_id'),
            'tag_filter' => trim((string) $request->input('tag_filter', '')),
            'campaign_filter' => trim((string) $request->input('campaign_filter', '')),
            'created_range' => trim((string) $request->input('created_range', 'all')),
            'sort' => trim((string) $request->input('sort', 'name')),
            'direction' => trim((string) $request->input('direction', 'asc')),
            'per_page' => trim((string) $request->input('per_page', '20')),
        ]);

        $currentSearchSaved = false;

        if (! empty($currentFilterParameters)) {
            $currentHash = hash('sha256', json_encode($currentFilterParameters));
            $currentSearchSaved = SavedSearch::query()
                ->where('resource', 'companies')
                ->where('parameter_hash', $currentHash)
                ->exists();
        }

        $perPageInput = strtolower((string) $request->input('per_page', '20'));
        $perPage = $perPageInput === 'all' ? max(1, Company::count()) : max(10, min(100, (int) $perPageInput));
        $sortField = (string) $request->input('sort', 'name');
        $sortDirection = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $companyQuery = $this->applyCompanyFilters(
            Company::query()->with(['status', 'tags']),
            $request,
            trim((string) $request->input('search', $request->input('search_term', '')))
        );
        $this->applyCompanySort($companyQuery, $sortField, $sortDirection);
        $companies = $companyQuery->paginate($perPage)->withQueryString();

        return view('crm::companies.index', [
            'companies' => $companies,
            'filters' => [
                'search' => (string) ($currentFilterParameters['search'] ?? ''),
                'status_id' => (string) ($currentFilterParameters['status_id'] ?? ''),
                'tag_filter' => (string) ($currentFilterParameters['tag_filter'] ?? ''),
                'campaign_filter' => (string) ($currentFilterParameters['campaign_filter'] ?? ''),
                'created_range' => (string) ($currentFilterParameters['created_range'] ?? 'all'),
                'sort' => (string) ($currentFilterParameters['sort'] ?? 'name'),
                'direction' => (string) ($currentFilterParameters['direction'] ?? 'asc'),
                'per_page' => (string) ($currentFilterParameters['per_page'] ?? '20'),
                'page' => max(1, (int) $request->integer('page', 1)),
            ],
            'statusOptions' => $statuses->map(fn (Status $status) => [
                'id' => (string) $status->id,
                'name' => $status->name,
            ]),
            'tagOptions' => $tags->map(fn (Tag $tag) => [
                'name' => $tag->name,
            ]),
            'campaignOptions' => $campaigns->map(fn (Tag $campaign) => [
                'name' => $campaign->name,
            ]),
            'totalCompanies' => Company::count(),
            'currentSearchSaved' => $currentSearchSaved,
            'createUrl' => route('crm.companies.create'),
            'bulkActionUrl' => route('crm.companies.bulk-action'),
            'dataUrl' => route('crm.companies.data'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tags = Tag::where('type', 'tag')->get();
        $campaigns = Tag::where('type', 'campaign')->get();
        $relationships = Relationship::orderBy('name')->get();
        $statuses = Status::orderBy('name')->get();

        return view('crm::companies.create', [
            'statusOptions' => $statuses->map(fn (Status $status) => [
                'id' => (string) $status->id,
                'name' => $status->name,
            ]),
            'relationshipOptions' => $relationships->map(fn (Relationship $relationship) => [
                'id' => (string) $relationship->id,
                'name' => $relationship->name,
            ]),
            'tagOptions' => $tags->map(fn (Tag $tag) => [
                'name' => $tag->name,
            ]),
            'campaignOptions' => $campaigns->map(fn (Tag $campaign) => [
                'name' => $campaign->name,
            ]),
        ]);
    }

    public function googleScrape(Request $request, GooglePlacesUsageDashboardService $dashboardService)
    {
        $rangeDays = (int) $request->integer('usage_range', 30);

        return view('crm::companies.google-scrape', $dashboardService->getDashboardData($rangeDays));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'needs_callback' => ['nullable', 'boolean'],
            'callback_at' => ['nullable', 'required_if:needs_callback,1', 'date'],
            'poc_name' => ['nullable', 'string', 'max:150'],
            'poc_email' => ['nullable', 'email', 'max:150'],
            'poc_phone' => ['nullable', 'string', 'max:50'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'status_id' => ['nullable', 'exists:'.TableNames::get('statuses').',id'],
            'relationship_id' => ['nullable', 'exists:'.TableNames::get('relationships').',id'],
            'opportunity_score_index' => ['nullable', 'integer', 'min:0', 'max:100'],
            'opportunity_summary' => ['nullable', 'string'],
            'meta' => ['nullable', 'json'],
        ]);

        $validated['needs_callback'] = $request->boolean('needs_callback');

        if (! $validated['needs_callback']) {
            $validated['callback_at'] = null;
        }

        $company = new Company($validated);
        $company->uuid = Str::uuid();
        $company->save();

        // Tags (comma-separated string or array)
        if ($request->filled('tags')) {
            $tags = is_array($request->tags)
                ? $request->tags
                : array_map('trim', explode(',', $request->tags));

            foreach ($tags as $tag) {
                $company->attachTag($tag, 'tag');
            }
        }

        if ($request->filled('campaigns')) {
            $campaigns = is_array($request->campaigns)
                ? $request->campaigns
                : array_map('trim', explode(',', $request->campaigns));

            foreach ($campaigns as $campaign) {
                $company->attachTag($campaign, 'campaign');
            }
        }

        if ($request->filled('initial_note')) {
            $company->notes()->create(['content' => $request->initial_note]);
        }

        return redirect()
            ->route('crm.companies.show', $company)
            ->with('success', 'Company added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        $company = self::loadModalCompany($company->id);
        $statuses = Status::orderBy('name')->get();
        $relationships = Relationship::orderBy('name')->get();

        return view('crm::companies.show', [
            'company' => $this->transformCompanyForShow($company),
            'statusOptions' => $statuses->map(fn (Status $status) => [
                'id' => (string) $status->id,
                'name' => $status->name,
            ]),
            'relationshipOptions' => $relationships->map(fn (Relationship $relationship) => [
                'id' => (string) $relationship->id,
                'name' => $relationship->name,
            ]),
        ]);
    }

    public function modal(Company $company)
    {
        if (request()->expectsJson()) {
            $company = self::loadModalCompany($company->id);
            $statuses = Status::orderBy('name')->get();
            $relationships = Relationship::orderBy('name')->get();

            return response()->json([
                'company' => $this->transformCompanyForShow($company),
                'statusOptions' => $statuses->map(fn (Status $status) => [
                    'id' => (string) $status->id,
                    'name' => $status->name,
                ])->values(),
                'relationshipOptions' => $relationships->map(fn (Relationship $relationship) => [
                    'id' => (string) $relationship->id,
                    'name' => $relationship->name,
                ])->values(),
            ]);
        }

        return view('crm::companies.partials.modal-content', [
            'company' => self::loadModalCompany($company->id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        $tags = Tag::where('type', 'tag')->get();
        $campaigns = Tag::where('type', 'campaign')->get();
        $relationships = Relationship::orderBy('name')->get();
        $statuses = Status::orderBy('name')->get();

        return view('crm::companies.edit', [
            'company' => $this->transformCompanyForEdit($company->load(['notes', 'tags'])),
            'statusOptions' => $statuses->map(fn (Status $status) => [
                'id' => (string) $status->id,
                'name' => $status->name,
            ]),
            'relationshipOptions' => $relationships->map(fn (Relationship $relationship) => [
                'id' => (string) $relationship->id,
                'name' => $relationship->name,
            ]),
            'tagOptions' => $tags->map(fn (Tag $tag) => [
                'name' => $tag->name,
            ]),
            'campaignOptions' => $campaigns->map(fn (Tag $campaign) => [
                'name' => $campaign->name,
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'needs_callback' => ['nullable', 'boolean'],
            'callback_at' => ['nullable', 'required_if:needs_callback,1', 'date'],
            'poc_name' => ['nullable', 'string', 'max:150'],
            'poc_email' => ['nullable', 'email', 'max:150'],
            'poc_phone' => ['nullable', 'string', 'max:50'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'status_id' => 'nullable|exists:'.TableNames::get('statuses').',id',
            'relationship_id' => ['nullable', 'exists:'.TableNames::get('relationships').',id'],
            'opportunity_score_index' => ['nullable', 'integer', 'min:0', 'max:100'],
            'opportunity_summary' => ['nullable', 'string'],
            'meta' => ['nullable', 'json'],
        ]);

        // Convert JSON string to array for the casted 'meta' attribute.
        if (array_key_exists('meta', $validated)) {
            $validated['meta'] = json_decode($validated['meta'] ?? '[]', true) ?: [];
        }

        $validated['needs_callback'] = $request->boolean('needs_callback');

        if (! $validated['needs_callback']) {
            $validated['callback_at'] = null;
        }

        // Ensure UUID is never changed from here (if it's in $fillable).
        unset($validated['uuid']);

        $company->fill($validated)->save();

        if ($request->filled('new_note')) {
            $company->notes()->create(['content' => $request->new_note]);
        }

        // Sync exactly what the form submitted so selected values persist on save.
        if ($request->exists('tags')) {
            $company->syncTagsWithType($this->normalizeTagValues($request->input('tags')), 'tag');
        }

        if ($request->exists('campaigns')) {
            $company->syncTagsWithType($this->normalizeTagValues($request->input('campaigns')), 'campaign');
        }

        if ($request->expectsJson()) {
            $company = self::loadModalCompany($company->id);

            return response()->json([
                'message' => 'Company updated successfully.',
                'company' => $this->transformCompanyForShow($company),
            ]);
        }

        return redirect()
            ->route('crm.companies.show', $company)
            ->with('success', 'Company updated successfully.');
    }

    public function updateCallback(Request $request, Company $company)
    {
        $validated = $request->validate([
            'needs_callback' => ['nullable', 'boolean'],
            'callback_at' => ['nullable', 'required_if:needs_callback,1', 'date'],
        ]);

        $needsCallback = $request->boolean('needs_callback');

        $company->update([
            'needs_callback' => $needsCallback,
            'callback_at' => $needsCallback ? ($validated['callback_at'] ?? null) : null,
        ]);

        if (! $request->expectsJson()) {
            return redirect()
                ->route('crm.companies.show', $company)
                ->with('success', 'Callback preference saved.');
        }

        $company = $company->fresh();

        return response()->json([
            'message' => 'Callback preference saved.',
            'needsCallback' => $company->needs_callback,
            'callbackAt' => optional($company->callback_at)->format('Y-m-d\TH:i'),
            'callbackAtIso' => $company->callback_at?->toIso8601String(),
            'callbackSummary' => view('crm::companies.partials.callback-summary', [
                'company' => $company,
            ])->render(),
        ]);
    }

    public function updateClassification(Request $request, Company $company)
    {
        $validated = $request->validate([
            'status_id' => ['required', 'exists:'.TableNames::get('statuses').',id'],
            'relationship_id' => ['required', 'exists:'.TableNames::get('relationships').',id'],
        ]);

        $company->update($validated);

        if (! $request->expectsJson()) {
            return redirect()
                ->route('crm.companies.show', $company)
                ->with('success', 'Company classification saved.');
        }

        return response()->json([
            'message' => 'Company classification saved.',
            'statusId' => (int) $company->status_id,
            'relationshipId' => (int) $company->relationship_id,
        ]);
    }

    public function storeNote(Request $request, Company $company)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $note = $company->notes()->create($validated);

        if (! $request->expectsJson()) {
            return redirect()
                ->route('crm.companies.show', $company)
                ->with('success', 'Note saved.');
        }

        return response()->json([
            'message' => 'Note saved.',
            'note' => $this->transformNote($note),
            'noteHtml' => view('crm::companies.partials.note-item', [
                'note' => $note,
            ])->render(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Company $company)
    {
        $company->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Company archived.',
            ]);
        }

        return redirect()->route('crm.companies.index')->with('success', 'Company archived.');
    }

    /**
     * Restored a trashed company.
     */
    public function restore($id)
    {
        $company = Company::withTrashed()->findOrFail($id);
        $this->authorize('restore', $company); // optional
        $company->restore();

        return redirect()->route('crm.companies.index')->with('success', 'Company restored.');
    }

    /**
     * Permanently delete a trashed company.
     */
    public function forceDelete($id)
    {
        $company = Company::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $company); // optional
        $company->forceDelete();

        return redirect()->route('crm.companies.index')->with('success', 'Company permanently deleted.');
    }

    // Bulk tag edit
    public function bulkTag(Request $request)
    {
        $validated = $request->validate([
            'companies' => 'required|array',
            'tags' => 'required|array',
        ]);

        $tags = $validated['tags'];
        $companies = Company::whereIn('id', $validated['companies'])->get();

        foreach ($companies as $company) {
            foreach ($tags as $tag) {
                $company->attachTag($tag, 'tag');
            }
        }

        return redirect()->back()->with('success', 'Tags applied successfully!');
    }

    public function getData(Request $request)
    {
        $companyTable = TableNames::get('companies');
        $query = Company::query()->with(['status', 'tags']);

        $draw = intval($request->get('draw'));
        $searchValue = trim((string) $request->input('search.value', ''));

        if ($searchValue === '') {
            $searchValue = trim((string) $request->input('search', $request->input('search_term', '')));
        }

        $query = $this->applyCompanyFilters($query, $request, $searchValue);

        if ($draw > 0) {
            $start = intval($request->get('start', 0));
            $length = intval($request->get('length', 10));
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $columns = $request->input('columns');
            $orderColumnName = isset($columns[$orderColumnIndex]['name'])
                ? $columns[$orderColumnIndex]['name']
                : 'created_at';

            $this->applyCompanySort($query, $orderColumnName, $orderDirection);

            $recordsTotal = Company::count();
            $recordsFiltered = (clone $query)->count($companyTable.'.id');

            if ($length === -1) {
                $companies = $query->get();
            } else {
                $companies = $query->skip($start)->take($length)->get();
            }

            $data = $companies->map(function ($company) {
                return [
                    'select' => view('crm::companies.partials.select', compact('company'))->render(),
                    'name' => view('crm::companies.partials.name', compact('company'))->render(),
                    'tags' => view('crm::companies.partials.tags', compact('company'))->render(),
                    'campaigns' => view('crm::companies.partials.campaigns', compact('company'))->render(),
                    'status' => view('crm::companies.partials.status', compact('company'))->render(),
                    'opportunity_score_index' => view('crm::companies.partials.opportunity-score', compact('company'))->render(),
                    'website' => view('crm::companies.partials.website', compact('company'))->render(),
                    'city' => e((string) ($company->city ?? '')),
                    'state' => e((string) ($company->state ?? '')),
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        }

        $perPageInput = strtolower((string) $request->input('per_page', '20'));
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = $perPageInput === 'all'
            ? max(1, Company::count())
            : max(10, min(1000, (int) $perPageInput));
        $sortField = (string) $request->input('sort', 'name');
        $sortDirection = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $this->applyCompanySort($query, $sortField, $sortDirection);

        $paginator = $query
            ->paginate($perPage, [$companyTable.'.*'], 'page', $page)
            ->withQueryString();

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (Company $company) => $this->transformCompanyForTable($company)
            ),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function bulkAction(Request $request)
    {
        $companyTable = TableNames::get('companies');
        $validated = $request->validate([
            'bulk_action' => ['nullable', 'string', 'in:bulk_tag,bulk_campaign,bulk_delete,export_csv,export_excel,export_pdf,export_vcard,export_avery_pdf,ai_enrich_clean,ai_opportunity_score'],
            'scope' => ['nullable', 'string', 'in:selected,filtered'],
            'companies' => ['nullable', 'array'],
            'companies.*' => ['integer', 'exists:'.$companyTable.',id'],
            'tags' => ['nullable', 'array'],
            'campaigns' => ['nullable', 'array'],
            'search_term' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer', 'exists:'.TableNames::get('statuses').',id'],
            'tag_filter' => ['nullable', 'string'],
            'campaign_filter' => ['nullable', 'string'],
            'created_range' => ['nullable', 'string', 'in:all,last_30,last_90,this_year'],
        ]);

        $scope = $validated['scope'] ?? 'selected';
        $action = $validated['bulk_action'] ?? '';

        if ($action === '') {
            return back()->with('success', 'Choose a bulk action first.');
        }

        $query = Company::query()->with('status');
        if ($scope === 'filtered') {
            $query = $this->applyCompanyFilters($query, $request, trim((string) ($validated['search_term'] ?? '')));
        } else {
            $companyIds = $validated['companies'] ?? [];
            if (empty($companyIds)) {
                return back()->with('success', 'No companies selected.');
            }
            $query->whereIn($companyTable.'.id', $companyIds);
        }

        if ($action === 'bulk_tag') {
            $tags = $validated['tags'] ?? [];
            if (empty($tags)) {
                return back()->with('success', 'Select at least one tag before applying bulk tagging.');
            }

            $companies = $query->get();
            foreach ($companies as $company) {
                foreach ($tags as $tag) {
                    $company->attachTag($tag, 'tag');
                }
            }

            return back()->with('success', 'Bulk tagging complete.');
        }

        if ($action === 'bulk_campaign') {
            $campaigns = $validated['campaigns'] ?? [];
            if (empty($campaigns)) {
                return back()->with('success', 'Select at least one campaign before applying bulk campaign assignment.');
            }

            $companies = $query->get();
            foreach ($companies as $company) {
                foreach ($campaigns as $campaign) {
                    $company->attachTag($campaign, 'campaign');
                }
            }

            return back()->with('success', 'Bulk campaign assignment complete.');
        }

        if ($action === 'ai_enrich_clean') {
            return back()->with('success', 'Use the Run AI Enrich + Clean button to process selected companies.');
        }

        if ($action === 'ai_opportunity_score') {
            return back()->with('success', 'Use the Run Opportunity Scoring button to process selected companies.');
        }

        if ($action === 'bulk_delete') {
            $deletedCount = (clone $query)->count($companyTable.'.id');
            if ($deletedCount === 0) {
                return back()->with('success', 'No companies matched your selected criteria.');
            }

            $query->delete();

            $noun = $deletedCount === 1 ? 'company' : 'companies';

            return back()->with('success', "Deleted {$deletedCount} {$noun}.");
        }

        $companies = $query->orderBy($companyTable.'.name')->get();
        if ($companies->isEmpty()) {
            return back()->with('success', 'No companies matched your selected criteria.');
        }

        if ($action === 'export_excel') {
            return $this->streamCompanySpreadsheet($companies);
        }

        if ($action === 'export_pdf') {
            return $this->streamCompanyListPdf($companies);
        }

        if ($action === 'export_vcard') {
            return $this->streamCompanyVcard($companies);
        }

        if ($action === 'export_avery_pdf') {
            return $this->streamCompanyAveryLabelsPdf($companies);
        }

        return $this->streamCompanyCsv($companies);
    }

    public function sortTags(Request $request)
    {
        $tag = $request->query('tag');

        if ($tag) {
            $companies = Company::withAnyTags([$tag], 'tag')->get();
        } else {
            $companies = Company::all();
        }

        return view('crm::companies.sorter', [
            'companies' => $companies,
            'activeTag' => $tag,
        ]);
    }

    public function aiEnrichAndClean(Request $request, Company $company, CompanyAiEnrichmentService $enrichmentService): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $provider = trim((string) ($validated['provider'] ?? 'openai'));
        $model = trim((string) ($validated['model'] ?? 'gpt-4.1-mini'));
        $overwrite = (bool) ($validated['overwrite'] ?? false);

        try {
            $result = $enrichmentService->enrichCompany(
                company: $company,
                provider: $provider !== '' ? $provider : 'openai',
                model: $model !== '' ? $model : 'gpt-4.1-mini',
                manualWebsiteCandidate: null,
                overwrite: $overwrite,
                persist: true
            );
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'company_id' => $company->id,
                'message' => 'AI enrich/clean failed: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'company_id' => $company->id,
            'applied_count' => count($result['applied'] ?? []),
            'cleanup_count' => count($result['cleanup'] ?? []),
        ]);
    }

    public function aiOpportunityScore(Request $request, Company $company, CompanyOpportunityScoringService $scoringService): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
        ]);

        $provider = trim((string) ($validated['provider'] ?? 'openai'));
        $model = trim((string) ($validated['model'] ?? 'gpt-4.1-mini'));

        try {
            $result = $scoringService->scoreCompany(
                company: $company,
                provider: $provider !== '' ? $provider : 'openai',
                model: $model !== '' ? $model : 'gpt-4.1-mini',
                persist: true
            );
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'company_id' => $company->id,
                'message' => 'Opportunity scoring failed: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'company_id' => $company->id,
            'score' => $result['score'] ?? 0,
            'auto_zero' => (bool) ($result['auto_zero'] ?? false),
        ]);
    }

    private function applyCompanyFilters($query, Request $request, string $searchTerm = '')
    {
        $companyTable = TableNames::get('companies');
        $statusId = $request->input('status_id');
        $tagFilter = trim((string) $request->input('tag_filter', ''));
        $campaignFilter = trim((string) $request->input('campaign_filter', ''));
        $createdRange = $request->input('created_range', 'all');

        if ($searchTerm !== '') {
            $query->where(function ($q) use ($companyTable, $searchTerm) {
                $q->where($companyTable.'.name', 'like', "%{$searchTerm}%")
                    ->orWhere($companyTable.'.website', 'like', "%{$searchTerm}%")
                    ->orWhere($companyTable.'.email', 'like', "%{$searchTerm}%")
                    ->orWhere($companyTable.'.phone', 'like', "%{$searchTerm}%")
                    ->orWhere($companyTable.'.poc_name', 'like', "%{$searchTerm}%")
                    ->orWhere($companyTable.'.poc_email', 'like', "%{$searchTerm}%")
                    ->orWhereHas('status', function ($statusQuery) use ($searchTerm) {
                        $statusQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('tags', function ($tagQuery) use ($searchTerm) {
                        $tagQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        if (! empty($statusId)) {
            $query->where($companyTable.'.status_id', $statusId);
        }

        if ($tagFilter !== '') {
            $query->withAnyTags([$tagFilter], 'tag');
        }

        if ($campaignFilter !== '') {
            $query->withAnyTags([$campaignFilter], 'campaign');
        }

        if ($createdRange === 'last_30') {
            $query->where($companyTable.'.created_at', '>=', now()->subDays(30));
        } elseif ($createdRange === 'last_90') {
            $query->where($companyTable.'.created_at', '>=', now()->subDays(90));
        } elseif ($createdRange === 'this_year') {
            $query->whereYear($companyTable.'.created_at', now()->year);
        }

        return $query;
    }

    private function streamCompanyCsv($companies): StreamedResponse
    {
        $filename = 'companies-'.now()->format('Ymd-His').'.csv';
        $headers = $this->companyExportHeaders();

        return response()->streamDownload(function () use ($companies, $headers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $headers);
            foreach ($companies as $company) {
                fputcsv($handle, $this->companyExportRow($company));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function streamCompanySpreadsheet($companies): StreamedResponse
    {
        $filename = 'companies-'.now()->format('Ymd-His').'.xls';
        $headers = $this->companyExportHeaders();

        return response()->streamDownload(function () use ($companies, $headers) {
            echo '<table border="1">';
            echo '<tr>';
            foreach ($headers as $header) {
                echo '<th>'.e($header).'</th>';
            }
            echo '</tr>';
            foreach ($companies as $company) {
                echo '<tr>';
                foreach ($this->companyExportRow($company) as $value) {
                    echo '<td>'.e((string) $value).'</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel']);
    }

    private function companyExportHeaders(): array
    {
        return [
            'ID',
            'UUID',
            'Name',
            'Website',
            'Email',
            'Phone',
            'POC Name',
            'POC Email',
            'POC Phone',
            'Address Line 1',
            'Address Line 2',
            'City',
            'State',
            'Postal Code',
            'Country',
            'Source',
            'Status ID',
            'Status',
            'Opportunity Score Index',
            'Opportunity Summary',
            'Meta',
            'Tags',
            'Campaigns',
            'Created At',
            'Updated At',
            'Deleted At',
        ];
    }

    private function companyExportRow(Company $company): array
    {
        $meta = $company->meta;
        if (is_array($meta)) {
            $meta = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $tags = $company->relationLoaded('tags')
            ? $company->tags->where('type', 'tag')->pluck('name')->implode(', ')
            : $company->tagsWithType('tag')->pluck('name')->implode(', ');
        $campaigns = $company->relationLoaded('tags')
            ? $company->tags->where('type', 'campaign')->pluck('name')->implode(', ')
            : $company->tagsWithType('campaign')->pluck('name')->implode(', ');

        return [
            $company->id,
            $company->uuid,
            $company->name,
            $company->website,
            $company->email,
            $company->phone,
            $company->poc_name,
            $company->poc_email,
            $company->poc_phone,
            $company->address_line1,
            $company->address_line2,
            $company->city,
            $company->state,
            $company->postal_code,
            $company->country,
            $company->source,
            $company->status_id,
            optional($company->status)->name,
            $company->opportunity_score_index,
            $company->opportunity_summary,
            $meta,
            $tags,
            $campaigns,
            optional($company->created_at)->toDateTimeString(),
            optional($company->updated_at)->toDateTimeString(),
            optional($company->deleted_at)->toDateTimeString(),
        ];
    }

    private function streamCompanyAveryLabelsPdf($companies): Response
    {
        $labels = $companies->map(function ($company) {
            $line1 = trim((string) ($company->alias ?: $company->name));

            $line2 = trim(implode(', ', array_filter([
                $company->address_line1,
                $company->address_line2,
            ])));

            $cityState = trim(implode(', ', array_filter([
                $company->city,
                $company->state,
            ])));

            $cityStateZip = trim(implode(' ', array_filter([
                $cityState,
                $this->formatAveryZip((string) $company->postal_code),
            ])));

            $line3 = $cityStateZip;

            // Some imported records store a full one-line address in address_line1.
            // Avoid printing duplicated city/state/zip on line 3 in those cases.
            if ($this->lineAlreadyContainsCityStateZip($line2, $company)) {
                $line3 = '';
            }

            return [
                'line1' => $line1,
                'line2' => $line2,
                'line3' => $line3,
            ];
        })->values()->all();

        $pdf = Avery94200Pdf::generateLabels($labels);
        $fileName = 'companies-avery-94200-'.now()->format('Ymd-His').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function streamCompanyListPdf($companies): Response
    {
        $entries = $companies->map(function (Company $company): array {
            return [
                'name' => (string) $company->name,
                'address_line1' => (string) ($company->address_line1 ?? ''),
                'address_line2' => (string) ($company->address_line2 ?? ''),
                'city' => (string) ($company->city ?? ''),
                'state' => (string) ($company->state ?? ''),
                'postal_code' => (string) ($company->postal_code ?? ''),
                'country' => (string) ($company->country ?? ''),
                'website' => (string) ($company->website ?? ''),
                'phone' => (string) ($company->phone ?? ''),
                'email' => (string) ($company->email ?? ''),
            ];
        })->values()->all();

        $title = count($entries).' Selected Companies on '.now()->toDateString();
        $pdf = CompanyListPdf::generate($entries, $title);
        $fileName = 'companies-list-'.now()->format('Ymd-His').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function streamCompanyVcard($companies): Response
    {
        $fileName = 'companies-'.now()->format('Ymd-His').'.vcf';
        $cards = [];

        foreach ($companies as $company) {
            $fullName = trim((string) ($company->poc_name ?: $company->name));
            $org = trim((string) $company->name);
            $email = trim((string) ($company->poc_email ?: $company->email));
            $phone = trim((string) ($company->poc_phone ?: $company->phone));
            $website = trim((string) $company->website);

            $addressParts = [
                trim((string) $company->address_line1),
                trim((string) $company->address_line2),
                trim((string) $company->city),
                trim((string) $company->state),
                trim((string) $company->postal_code),
                trim((string) $company->country),
            ];

            $card = [
                'BEGIN:VCARD',
                'VERSION:3.0',
                'FN:'.$this->escapeVcardValue($fullName),
                'N:;'.$this->escapeVcardValue($fullName).';;;',
                'ORG:'.$this->escapeVcardValue($org),
            ];

            if ($email !== '') {
                $card[] = 'EMAIL;TYPE=INTERNET:'.$this->escapeVcardValue($email);
            }

            if ($phone !== '') {
                $card[] = 'TEL;TYPE=WORK,VOICE:'.$this->escapeVcardValue($phone);
            }

            if ($website !== '') {
                $card[] = 'URL:'.$this->escapeVcardValue($website);
            }

            if (implode('', $addressParts) !== '') {
                $card[] = 'ADR;TYPE=WORK:;;'
                    .$this->escapeVcardValue($addressParts[0]).';'
                    .$this->escapeVcardValue($addressParts[1]).';'
                    .$this->escapeVcardValue($addressParts[2]).';'
                    .$this->escapeVcardValue($addressParts[3]).';'
                    .$this->escapeVcardValue($addressParts[4]).';'
                    .$this->escapeVcardValue($addressParts[5]);
            }

            $card[] = 'END:VCARD';
            $cards[] = implode("\r\n", $card);
        }

        $content = implode("\r\n", $cards)."\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/vcard; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function escapeVcardValue(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r", "\n"],
            ['\\\\', '\;', '\,', '', '\n'],
            $value
        );
    }

    private function lineAlreadyContainsCityStateZip(string $line, Company $company): bool
    {
        $normalizedLine = $this->normalizeAddressText($line);
        if ($normalizedLine === '') {
            return false;
        }

        $city = $this->normalizeAddressText((string) $company->city);
        $state = $this->normalizeAddressText((string) $company->state);
        $postalCode = $this->normalizeAddressText($this->formatAveryZip((string) $company->postal_code));

        $hasCity = $city !== '' && str_contains($normalizedLine, $city);
        $hasState = $state !== '' && str_contains($normalizedLine, $state);
        $hasPostalCode = $postalCode !== '' && str_contains($normalizedLine, $postalCode);

        return $hasCity && ($hasState || $hasPostalCode);
    }

    private function formatAveryZip(string $postalCode): string
    {
        $digits = preg_replace('/\D+/', '', trim($postalCode)) ?? '';

        return substr($digits, 0, 5);
    }

    private function applyCompanySort($query, string $sortField, string $sortDirection): void
    {
        $companiesTable = TableNames::get('companies');

        if ($sortField === 'status') {
            $statusesTable = TableNames::get('statuses');

            $query->leftJoin($statusesTable, "{$statusesTable}.id", '=', "{$companiesTable}.status_id")
                ->select("{$companiesTable}.*")
                ->orderBy("{$statusesTable}.name", $sortDirection)
                ->orderBy("{$companiesTable}.name");

            return;
        }

        if (in_array($sortField, ['tags', 'campaigns'], true)) {
            $type = $sortField === 'campaigns' ? 'campaign' : 'tag';
            $nameSubquery = DB::table(TableNames::get('tags').' as sort_tags')
                ->join(TableNames::get('taggables').' as sort_taggables', 'sort_taggables.tag_id', '=', 'sort_tags.id')
                ->whereColumn('sort_taggables.taggable_id', "{$companiesTable}.id")
                ->where('sort_taggables.taggable_type', Company::class)
                ->where('sort_tags.type', $type)
                ->selectRaw('MIN(sort_tags.name)');

            $query->orderBy($nameSubquery, $sortDirection)
                ->orderBy("{$companiesTable}.name");

            return;
        }

        if (in_array($sortField, ['name', 'website', 'city', 'state', 'opportunity_score_index', 'created_at'], true)) {
            $query->orderBy("{$companiesTable}.{$sortField}", $sortDirection);

            if ($sortField !== 'name') {
                $query->orderBy("{$companiesTable}.name");
            }

            return;
        }

        $query->orderBy("{$companiesTable}.created_at", 'desc');
    }

    private function normalizeAddressText(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function normalizedSearchParameters(array $raw): array
    {
        $parameters = [
            'search' => trim((string) ($raw['search'] ?? $raw['search_term'] ?? '')),
            'status_id' => $raw['status_id'] ?? null,
            'tag_filter' => trim((string) ($raw['tag_filter'] ?? '')),
            'campaign_filter' => trim((string) ($raw['campaign_filter'] ?? '')),
            'created_range' => trim((string) ($raw['created_range'] ?? 'all')),
            'sort' => trim((string) ($raw['sort'] ?? 'name')),
            'direction' => trim((string) ($raw['direction'] ?? 'asc')),
            'per_page' => trim((string) ($raw['per_page'] ?? '20')),
        ];

        $parameters = array_filter($parameters, function ($value, $key) {
            if (is_null($value) || $value === '') {
                return false;
            }

            if ($key === 'created_range') {
                return $value !== 'all';
            }

            if ($key === 'sort') {
                return $value !== 'name';
            }

            if ($key === 'direction') {
                return $value !== 'asc';
            }

            if ($key === 'per_page') {
                return $value !== '20';
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);

        ksort($parameters);

        return $parameters;
    }

    private function transformCompanyForTable(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'website' => $company->website,
            'city' => $company->city,
            'state' => $company->state,
            'status' => $company->status?->name,
            'status_id' => $company->status_id,
            'opportunity_score_index' => $company->opportunity_score_index,
            'tags' => $company->tags
                ->where('type', 'tag')
                ->pluck('name')
                ->values(),
            'campaigns' => $company->tags
                ->where('type', 'campaign')
                ->pluck('name')
                ->values(),
            'show_url' => route('crm.companies.show', $company),
            'modal_url' => route('crm.companies.modal', $company),
            'edit_url' => route('crm.companies.edit', $company),
            'delete_url' => route('crm.companies.destroy', $company),
        ];
    }

    private function transformCompanyForEdit(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'alias' => $company->alias,
            'website' => $company->website,
            'email' => $company->email,
            'phone' => $company->phone,
            'needs_callback' => $company->needs_callback,
            'callback_at' => $company->callback_at?->format('Y-m-d\TH:i'),
            'poc_name' => $company->poc_name,
            'poc_email' => $company->poc_email,
            'poc_phone' => $company->poc_phone,
            'address_line1' => $company->address_line1,
            'address_line2' => $company->address_line2,
            'city' => $company->city,
            'state' => $company->state,
            'postal_code' => $company->postal_code,
            'country' => $company->country,
            'source' => $company->source,
            'status_id' => (string) ($company->status_id ?? ''),
            'relationship_id' => (string) ($company->relationship_id ?? ''),
            'opportunity_score_index' => is_null($company->opportunity_score_index)
                ? ''
                : (string) $company->opportunity_score_index,
            'opportunity_summary' => $company->opportunity_summary ?? '',
            'meta' => empty($company->meta)
                ? ''
                : json_encode($company->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'tags' => $company->tagsWithType('tag')->pluck('name')->implode(', '),
            'campaigns' => $company->tagsWithType('campaign')->pluck('name')->implode(', '),
            'notes' => $company->notes->map(fn (Note $note) => $this->transformNote($note))->values(),
        ];
    }

    private function transformCompanyForShow(Company $company): array
    {
        $voiceNumber = preg_replace('/\D+/', '', (string) $company->phone) ?? '';
        $voiceNumber = $voiceNumber === ''
            ? ''
            : (str_starts_with($voiceNumber, '1') ? '+'.$voiceNumber : '+1'.$voiceNumber);

        return [
            'id' => $company->id,
            'name' => $company->name,
            'alias' => $company->alias,
            'website' => $company->website,
            'email' => $company->email,
            'phone' => $company->phone,
            'google_voice_url' => $voiceNumber === ''
                ? null
                : 'https://voice.google.com/u/0/calls?a=nc,'.rawurlencode($voiceNumber),
            'needs_callback' => $company->needs_callback,
            'callback_at' => $company->callback_at?->format('Y-m-d\TH:i'),
            'callback_at_label' => $company->callback_at?->format('M d, Y g:i A'),
            'poc_name' => $company->poc_name,
            'poc_email' => $company->poc_email,
            'poc_phone' => $company->poc_phone,
            'address_line1' => $company->address_line1,
            'address_line2' => $company->address_line2,
            'city' => $company->city,
            'state' => $company->state,
            'postal_code' => $company->postal_code,
            'country' => $company->country,
            'source' => $company->source,
            'status' => $company->status ? [
                'id' => (string) $company->status->id,
                'name' => $company->status->name,
                'description' => $company->status->description,
            ] : null,
            'relationship' => $company->relationship ? [
                'id' => (string) $company->relationship->id,
                'name' => $company->relationship->name,
                'description' => $company->relationship->description,
            ] : null,
            'status_id' => (string) ($company->status_id ?? ''),
            'relationship_id' => (string) ($company->relationship_id ?? ''),
            'opportunity_score_index' => $company->opportunity_score_index,
            'opportunity_summary' => $company->opportunity_summary,
            'meta' => $company->meta ?? [],
            'tags' => $company->tagsWithType('tag')->pluck('name')->values(),
            'campaigns' => $company->tagsWithType('campaign')->pluck('name')->values(),
            'update_url' => route('crm.companies.update', $company),
            'notes' => $company->notes->map(fn (Note $note) => $this->transformNote($note))->values(),
            'phone_calls' => $company->phoneCalls->map(function ($phoneCall) {
                return [
                    'id' => $phoneCall->id,
                    'called_at' => $phoneCall->called_at?->toIso8601String(),
                    'called_at_label' => $phoneCall->called_at?->format('M d, Y g:i A') ?? 'No date recorded',
                    'notes' => $phoneCall->notes,
                ];
            })->values(),
            'edit_url' => route('crm.companies.edit', $company),
            'delete_url' => route('crm.companies.destroy', $company),
            'callback_update_url' => route('crm.companies.callback.update', $company),
            'classification_update_url' => route('crm.companies.classification.update', $company),
            'notes_store_url' => route('crm.companies.notes.store', $company),
            'phone_calls_store_url' => route('crm.companies.phone-calls.store', $company),
            'index_url' => route('crm.companies.index'),
        ];
    }

    private function transformNote(Note $note): array
    {
        return [
            'id' => $note->id,
            'content' => $note->content,
            'created_at' => $note->created_at?->toIso8601String(),
            'created_at_label' => $note->created_at?->format('M d, Y g:i A'),
        ];
    }

    private function normalizeTagValues(string|array|null $rawValues): array
    {
        if (is_array($rawValues)) {
            $values = $rawValues;
        } else {
            $values = explode(',', (string) $rawValues);
        }

        $values = array_map(static fn ($value) => trim((string) $value), $values);
        $values = array_filter($values, static fn ($value) => $value !== '');

        return array_values(array_unique($values));
    }

    public static function loadModalCompany(int $companyId): Company
    {
        return Company::query()
            ->with([
                'status',
                'relationship',
                'tags',
                'notes' => fn ($query) => $query->latest(),
                'phoneCalls' => fn ($query) => $query->latest('called_at'),
            ])
            ->findOrFail($companyId);
    }
}
