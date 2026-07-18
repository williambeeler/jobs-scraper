<?php

namespace App\Http\Controllers;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

class RunScrapeSchedulesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $expectedToken = (string) config('services.scrape_cron.token');
        $providedToken = $request->bearerToken();

        abort_unless(
            $expectedToken !== ''
                && is_string($providedToken)
                && hash_equals($expectedToken, $providedToken),
            403,
        );

        $scrapeExitCode = Artisan::call('jobs:run-schedules', ['--no-interaction' => true]);
        $scheduleExitCode = Artisan::call('schedule:run', ['--no-interaction' => true]);

        return response('', $scrapeExitCode === Command::SUCCESS && $scheduleExitCode === Command::SUCCESS ? 204 : 500);
    }
}
