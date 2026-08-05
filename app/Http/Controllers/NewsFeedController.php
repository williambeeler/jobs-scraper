<?php

namespace App\Http\Controllers;

use App\Models\NewsFeed;
use App\Services\News\RssFeedImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class NewsFeedController extends Controller
{
    public function index(): View
    {
        return view('news.feeds.index', [
            'feeds' => NewsFeed::query()
                ->withCount('articles')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, RssFeedImporter $importer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'url' => [
                'required',
                'url:http,https',
                'max:2048',
            ],
        ]);

        $url = trim($validated['url']);

        if (NewsFeed::query()->where('url_hash', hash('sha256', $url))->exists()) {
            throw ValidationException::withMessages([
                'url' => 'That feed has already been added.',
            ]);
        }

        $feed = NewsFeed::query()->create([
            'name' => trim($validated['name'] ?? '') ?: parse_url($url, PHP_URL_HOST) ?: 'Untitled feed',
            'url' => $url,
            'url_hash' => hash('sha256', $url),
        ]);

        try {
            $result = $importer->import($feed);

            return redirect()->route('news.feeds.index')
                ->with('status', "Feed added. Imported {$result['imported']} articles.");
        } catch (Throwable) {
            return redirect()->route('news.feeds.index')
                ->with('error', 'The feed was saved, but its first import failed. You can retry it below.');
        }
    }

    public function refresh(NewsFeed $feed, RssFeedImporter $importer): RedirectResponse
    {
        try {
            $result = $importer->import($feed);

            return back()->with('status', "{$feed->name} refreshed: {$result['imported']} new, {$result['updated']} updated.");
        } catch (Throwable) {
            return back()->with('error', "{$feed->name} could not be refreshed.");
        }
    }

    public function refreshAll(RssFeedImporter $importer): RedirectResponse
    {
        $imported = 0;
        $updated = 0;
        $failed = 0;

        NewsFeed::query()->where('is_active', true)->each(function (NewsFeed $feed) use (
            $importer,
            &$imported,
            &$updated,
            &$failed,
        ): void {
            try {
                $result = $importer->import($feed);
                $imported += $result['imported'];
                $updated += $result['updated'];
            } catch (Throwable) {
                $failed++;
            }
        });

        $message = "Feeds refreshed: {$imported} new, {$updated} updated.";

        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }

        return back()->with($failed > 0 ? 'error' : 'status', $message);
    }

    public function destroy(NewsFeed $feed): RedirectResponse
    {
        $feed->delete();

        return redirect()->route('news.feeds.index')->with('status', 'Feed removed.');
    }
}
