<?php

namespace App\Crm\Http\Controllers;

use App\Crm\Services\GoogleCloudPlatformService;
use App\Crm\Services\OpenAiPlatformService;
use App\Crm\Support\EnvValueStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ToolsController extends Controller
{
    public function googlePlatform()
    {
        return view('crm.tools.google-platform', app(GoogleCloudPlatformService::class)->getDashboardData());
    }

    public function redirectGoogleCloudOAuth(): RedirectResponse
    {
        if (! filled(config('services.google.oauth_client_id')) || ! filled(config('services.google.oauth_client_secret'))) {
            return redirect()
                ->route('crm.tools.googlePlatform')
                ->with('error', 'This CRM is missing its Google OAuth client configuration. Add GOOGLE_CLOUD_OAUTH_CLIENT_ID and GOOGLE_CLOUD_OAUTH_CLIENT_SECRET in the environment first.');
        }

        if (! filled(config('services.google.cloud_project_id'))) {
            return redirect()
                ->route('crm.tools.googlePlatform')
                ->with('error', 'This CRM is missing GOOGLE_CLOUD_PROJECT_ID, so Google Cloud data cannot be mapped to the right project yet.');
        }

        $state = Str::random(40);
        session(['google_cloud_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.google.oauth_client_id'),
            'redirect_uri' => route('crm.tools.googlePlatform.oauth.callback'),
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/cloud-platform.read-only',
                'https://www.googleapis.com/auth/monitoring.read',
                'https://www.googleapis.com/auth/cloud-billing.readonly',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function handleGoogleCloudOAuthCallback(
        Request $request,
        EnvValueStore $envValueStore,
        GoogleCloudPlatformService $googleCloudPlatformService
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'error' => ['nullable', 'string'],
        ]);

        $expectedState = session('google_cloud_oauth_state');
        session()->forget('google_cloud_oauth_state');

        if (filled($validated['error'] ?? null)) {
            return redirect()
                ->route('crm.tools.googlePlatform')
                ->with('error', 'Google authorization failed: '.$validated['error']);
        }

        if (! is_string($expectedState) || $expectedState === '' || ($validated['state'] ?? null) !== $expectedState) {
            return redirect()
                ->route('crm.tools.googlePlatform')
                ->with('error', 'Google authorization state could not be verified.');
        }

        if (! filled($validated['code'] ?? null)) {
            return redirect()
                ->route('crm.tools.googlePlatform')
                ->with('error', 'Google authorization did not return an authorization code.');
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post('https://oauth2.googleapis.com/token', [
                    'code' => $validated['code'],
                    'client_id' => config('services.google.oauth_client_id'),
                    'client_secret' => config('services.google.oauth_client_secret'),
                    'redirect_uri' => route('crm.tools.googlePlatform.oauth.callback'),
                    'grant_type' => 'authorization_code',
                ]);

            if (! $response->ok()) {
                $message = data_get($response->json(), 'error_description')
                    ?: data_get($response->json(), 'error')
                    ?: 'Google token exchange failed.';

                return redirect()
                    ->route('crm.tools.googlePlatform')
                    ->with('error', $message);
            }

            $refreshToken = (string) $response->json('refresh_token');

            if ($refreshToken === '') {
                return redirect()
                    ->route('crm.tools.googlePlatform')
                    ->with('error', 'Google did not return a refresh token. Try connecting again after revoking the previous grant.');
            }

            $envValueStore->set('GOOGLE_CLOUD_OAUTH_REFRESH_TOKEN', $refreshToken);
            config()->set('services.google.oauth_refresh_token', $refreshToken);
            $googleCloudPlatformService->clearCache();

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable $e) {
            return redirect()
                ->route('crm.tools.googlePlatform')
                ->with('error', 'Google OAuth callback failed: '.$e->getMessage());
        }

        return redirect()
            ->route('crm.tools.googlePlatform')
            ->with('success', 'Google Cloud connected successfully.');
    }

    public function removeGoogleCloudCredentials(
        EnvValueStore $envValueStore,
        GoogleCloudPlatformService $googleCloudPlatformService
    ): RedirectResponse {
        try {
            $envValueStore->remove('GOOGLE_CLOUD_OAUTH_REFRESH_TOKEN');

            config()->set('services.google.oauth_refresh_token', null);
            $googleCloudPlatformService->clearCache();
            session()->forget('google_cloud_oauth_state');

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable $e) {
            return redirect()
                ->route('crm.tools.googlePlatform')
                ->with('error', 'Google Cloud credentials could not be removed: '.$e->getMessage());
        }

        return redirect()
            ->route('crm.tools.googlePlatform')
            ->with('success', 'Google account disconnected.');
    }

    public function openAiPlatform(OpenAiPlatformService $openAiPlatformService)
    {
        return view('crm.tools.openai-platform', $openAiPlatformService->getDashboardData());
    }

    public function storeOpenAiUsageKey(Request $request, EnvValueStore $envValueStore): RedirectResponse
    {
        $validated = $request->validate([
            'usage_api_key' => ['required', 'string', 'min:20'],
        ]);

        try {
            $usageApiKey = trim($validated['usage_api_key']);

            $envValueStore->set('OPENAI_USAGE_API_KEY', $usageApiKey);
            config()->set('services.openai.usage_api_key', $usageApiKey);
            app(OpenAiPlatformService::class)->clearCache();

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable $e) {
            return redirect()
                ->route('crm.tools.openAiPlatform')
                ->with('error', 'OpenAI usage key could not be saved: '.$e->getMessage());
        }

        return redirect()
            ->route('crm.tools.openAiPlatform')
            ->with('success', 'OpenAI usage key connected successfully.');
    }

    public function removeOpenAiUsageKey(EnvValueStore $envValueStore): RedirectResponse
    {
        try {
            $envValueStore->remove('OPENAI_USAGE_API_KEY');
            config()->set('services.openai.usage_api_key', null);
            app(OpenAiPlatformService::class)->clearCache();

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable $e) {
            return redirect()
                ->route('crm.tools.openAiPlatform')
                ->with('error', 'OpenAI usage key could not be removed: '.$e->getMessage());
        }

        return redirect()
            ->route('crm.tools.openAiPlatform')
            ->with('success', 'OpenAI usage key removed.');
    }
}
