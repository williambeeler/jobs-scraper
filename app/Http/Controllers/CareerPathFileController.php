<?php

namespace App\Http\Controllers;

use App\Models\CareerPath;
use App\Models\CareerPathFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CareerPathFileController extends Controller
{
    public function show(CareerPath $careerPath, CareerPathFile $careerPathFile): StreamedResponse
    {
        $this->ensureFileBelongsToPath($careerPath, $careerPathFile);

        return Storage::disk('local')->response(
            $careerPathFile->path,
            $careerPathFile->original_name,
            ['Content-Type' => $careerPathFile->mime_type ?: 'application/octet-stream'],
        );
    }

    public function download(CareerPath $careerPath, CareerPathFile $careerPathFile): StreamedResponse
    {
        $this->ensureFileBelongsToPath($careerPath, $careerPathFile);

        return Storage::disk('local')->download($careerPathFile->path, $careerPathFile->original_name);
    }

    public function destroy(CareerPath $careerPath, CareerPathFile $careerPathFile): RedirectResponse
    {
        $this->ensureFileBelongsToPath($careerPath, $careerPathFile);
        $careerPathFile->delete();

        return back()->with('status', 'File deleted.');
    }

    private function ensureFileBelongsToPath(CareerPath $careerPath, CareerPathFile $careerPathFile): void
    {
        abort_unless($careerPathFile->career_path_id === $careerPath->id, 404);
    }
}
