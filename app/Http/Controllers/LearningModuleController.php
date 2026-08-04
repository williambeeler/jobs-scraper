<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\LearningModule;
use App\Models\PortfolioProject;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LearningModuleController extends Controller
{
    public function storeForCertificate(Request $request, Certificate $certificate): RedirectResponse
    {
        $certificate->learningModules()->create($this->validated($request));

        return to_route('learning.certificates.show', $certificate)->with('status', 'Learning module added.');
    }

    public function storeForProject(Request $request, PortfolioProject $portfolioProject): RedirectResponse
    {
        $portfolioProject->learningModules()->create($this->validated($request));

        return to_route('learning.projects.show', $portfolioProject)->with('status', 'Learning module added.');
    }

    public function edit(LearningModule $learningModule): View
    {
        return view('learning.modules.edit', [
            'learningModule' => $learningModule->load('moduleable'),
            'cancelUrl' => $this->parentUrl($learningModule->moduleable),
        ]);
    }

    public function update(Request $request, LearningModule $learningModule): RedirectResponse
    {
        $learningModule->update($this->validated($request));

        return redirect($this->parentUrl($learningModule->moduleable))->with('status', 'Learning module updated.');
    }

    public function destroy(LearningModule $learningModule): RedirectResponse
    {
        $parent = $learningModule->moduleable;
        $learningModule->delete();

        return redirect($this->parentUrl($parent))->with('status', 'Learning module deleted.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
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

    private function parentUrl(Model $parent): string
    {
        return match (true) {
            $parent instanceof Certificate => route('learning.certificates.show', $parent),
            $parent instanceof PortfolioProject => route('learning.projects.show', $parent),
            default => route('learning'),
        };
    }
}
