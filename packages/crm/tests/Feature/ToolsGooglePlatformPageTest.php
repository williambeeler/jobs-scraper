<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use WilliamBeeler\Crm\Services\GoogleCloudPlatformService;
use WilliamBeeler\Crm\Support\EnvValueStore;

class ToolsGooglePlatformPageTest extends TestCase
{
    public function test_google_platform_page_loads(): void
    {
        $service = Mockery::mock(GoogleCloudPlatformService::class);
        $service->shouldReceive('getDashboardData')
            ->once()
            ->andReturn([
                'hasPlacesApiKey' => true,
                'hasCloudCredentials' => true,
                'hasOAuthClientConfig' => true,
                'hasBillingAccountId' => true,
                'connectionLabel' => 'Connected',
                'project' => [
                    'id' => 'demo-project',
                    'has_oauth_client_config' => true,
                ],
                'billing' => [
                    'status' => 'available',
                    'billing_enabled' => true,
                    'billing_account_name' => 'billingAccounts/123456-ABCDEF-789012',
                    'message' => 'Billing is enabled for this Google Cloud project.',
                ],
                'enabledMapsServices' => [
                    'status' => 'available',
                    'message' => 'Enabled Google Maps related services for this project.',
                    'items' => [
                        [
                            'name' => 'places-backend.googleapis.com',
                            'title' => 'Places API',
                        ],
                    ],
                ],
                'budgets' => [
                    'status' => 'available',
                    'message' => 'Budgets linked to the configured billing account.',
                    'items' => [
                        [
                            'display_name' => 'Maps Budget',
                            'amount' => 'USD 120.00',
                            'thresholds' => '50%, 90%',
                        ],
                    ],
                ],
                'requests' => [
                    'status' => 'available',
                    'total' => 471,
                    'message' => 'Daily Google Maps request count from Cloud Monitoring.',
                    'range_label' => 'March 2, 2026 to March 16, 2026',
                    'daily' => [],
                ],
                'errorMetrics' => [
                    'status' => 'available',
                    'total' => 12,
                    'message' => 'Daily Google Maps 4xx and 5xx request count from Cloud Monitoring.',
                    'range_label' => 'March 2, 2026 to March 16, 2026',
                    'daily' => [],
                ],
                'latency' => [
                    'status' => 'available',
                    'median_ms' => 123.45,
                    'message' => 'Daily median latency from Google Maps Platform monitoring.',
                    'range_label' => 'March 2, 2026 to March 16, 2026',
                    'daily' => [],
                ],
            ]);

        $this->app->instance(GoogleCloudPlatformService::class, $service);

        $response = $this->get(route('tools.googlePlatform'));

        $response->assertOk();
        $response->assertViewIs('crm::tools.google-platform');
        $response->assertViewHas('connectionLabel', 'Connected');
        $response->assertViewHas('hasCloudCredentials', true);
        $response->assertSee('Places API');
        $response->assertSee('Maps Budget');
    }

    public function test_google_platform_page_shows_missing_state_without_cloud_credentials(): void
    {
        $service = Mockery::mock(GoogleCloudPlatformService::class);
        $service->shouldReceive('getDashboardData')
            ->once()
            ->andReturn([
                'hasPlacesApiKey' => false,
                'hasCloudCredentials' => false,
                'hasOAuthClientConfig' => false,
                'hasBillingAccountId' => false,
                'connectionLabel' => 'Setup required',
                'project' => [
                    'id' => null,
                    'has_oauth_client_config' => false,
                ],
                'billing' => [
                    'status' => 'missing_credentials',
                    'billing_enabled' => null,
                    'billing_account_name' => null,
                    'message' => 'This CRM needs GOOGLE_CLOUD_PROJECT_ID, GOOGLE_CLOUD_OAUTH_CLIENT_ID, and GOOGLE_CLOUD_OAUTH_CLIENT_SECRET configured before you can connect with Google.',
                ],
                'enabledMapsServices' => [
                    'status' => 'missing_credentials',
                    'message' => 'Finish the Google app configuration in the environment before loading Maps services.',
                    'items' => [],
                ],
                'budgets' => [
                    'status' => 'missing_credentials',
                    'message' => 'Finish the Google app configuration in the environment before loading budgets.',
                    'items' => [],
                ],
                'requests' => [
                    'status' => 'error',
                    'total' => null,
                    'message' => 'Finish the Google app configuration in the environment before loading request volume.',
                    'range_label' => 'March 2, 2026 to March 16, 2026',
                    'daily' => [],
                ],
                'errorMetrics' => [
                    'status' => 'error',
                    'total' => null,
                    'message' => 'Finish the Google app configuration in the environment before loading error volume.',
                    'range_label' => 'March 2, 2026 to March 16, 2026',
                    'daily' => [],
                ],
                'latency' => [
                    'status' => 'error',
                    'median_ms' => null,
                    'message' => 'Finish the Google app configuration in the environment before loading median latency.',
                    'range_label' => 'March 2, 2026 to March 16, 2026',
                    'daily' => [],
                ],
            ]);

        $this->app->instance(GoogleCloudPlatformService::class, $service);

        $response = $this->get(route('tools.googlePlatform'));

        $response->assertOk();
        $response->assertViewIs('crm::tools.google-platform');
        $response->assertViewHas('connectionLabel', 'Setup required');
        $response->assertViewHas('hasOAuthClientConfig', false);
        $response->assertSee('Finish the Google app configuration in the environment before loading Maps services.');
    }

    public function test_google_connect_redirect_requires_app_configuration(): void
    {
        config()->set('services.google.cloud_project_id', null);
        config()->set('services.google.oauth_client_id', null);
        config()->set('services.google.oauth_client_secret', null);

        $response = $this->get(route('tools.googlePlatform.oauth.redirect'));

        $response->assertRedirect(route('tools.googlePlatform'));
        $response->assertSessionHas('error');
    }

    public function test_google_cloud_credentials_can_be_removed(): void
    {
        $envValueStore = Mockery::mock(EnvValueStore::class);
        $envValueStore->shouldReceive('remove')->once()->with('GOOGLE_CLOUD_OAUTH_REFRESH_TOKEN');

        $this->app->instance(EnvValueStore::class, $envValueStore);
        $this->mock(GoogleCloudPlatformService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearCache')->once();
        });

        $response = $this->post(route('tools.googlePlatform.remove'));

        $response->assertRedirect(route('tools.googlePlatform'));
        $response->assertSessionHas('success', 'Google account disconnected.');
    }
}
