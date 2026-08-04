<?php

namespace App\Http\Controllers;

use App\Models\CareerPath;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CareerPathController extends Controller
{
    public function index(): View
    {
        return view('learning.career-paths.index', [
            'careerPaths' => CareerPath::query()->withCount('agendas')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('learning.career-paths.create', ['careerPath' => new CareerPath]);
    }

    public function store(Request $request): RedirectResponse
    {
        $careerPath = CareerPath::query()->create($this->validated($request));
        $this->storeFiles($request, $careerPath);

        return to_route('learning.career-paths.show', $careerPath)->with('status', 'Career path added.');
    }

    public function show(CareerPath $careerPath): View
    {
        return view('learning.career-paths.show', [
            'careerPath' => $careerPath->load([
                'agendas' => fn ($query) => $query->orderBy('title'),
                'files' => fn ($query) => $query->latest(),
            ]),
        ]);
    }

    public function edit(CareerPath $careerPath): View
    {
        return view('learning.career-paths.edit', compact('careerPath'));
    }

    public function update(Request $request, CareerPath $careerPath): RedirectResponse
    {
        $careerPath->update($this->validated($request, $careerPath));
        $this->storeFiles($request, $careerPath);

        return to_route('learning.career-paths.show', $careerPath)->with('status', 'Career path updated.');
    }

    public function destroy(CareerPath $careerPath): RedirectResponse
    {
        $careerPath->delete();

        return to_route('learning.career-paths.index')->with('status', 'Career path deleted.');
    }

    private function validated(Request $request, ?CareerPath $careerPath = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('career_paths')->ignore($careerPath)],
            'description' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:12'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,csv,jpg,jpeg,png,gif,webp,svg'],
        ]);
        unset($validated['files']);
        $validated['title'] = trim($validated['title']);
        $validated['description'] = filled($validated['description'] ?? null)
            ? trim($validated['description'])
            : null;

        return $validated;
    }

    private function storeFiles(Request $request, CareerPath $careerPath): void
    {
        foreach ($request->file('files', []) as $file) {
            $path = $file->store("career-paths/{$careerPath->id}", 'local');

            $careerPath->files()->create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }
}
