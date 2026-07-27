<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use App\Models\Note;
use App\Support\NoteHtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JobListingNoteController extends Controller
{
    public function store(
        Request $request,
        JobListing $jobListing,
        NoteHtmlSanitizer $sanitizer,
    ): RedirectResponse {
        $validated = $request->validateWithBag('listingNote', [
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $body = $sanitizer->sanitize($validated['body']);

        if (blank(strip_tags($body))) {
            return back()->withErrors([
                'body' => 'The note must contain some text.',
            ], 'listingNote');
        }

        $jobListing->notes()->create(['body' => $body]);

        return back()->with('status', 'Note added.');
    }

    public function destroy(JobListing $jobListing, Note $note): RedirectResponse
    {
        abort_unless(
            $note->notable_type === $jobListing->getMorphClass()
            && $note->notable_id === $jobListing->id,
            404,
        );

        $note->delete();

        return back()->with('status', 'Note deleted.');
    }
}
