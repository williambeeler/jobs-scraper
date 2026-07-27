<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use WilliamBeeler\Crm\Services\OpenAiPlatformService;
use WilliamBeeler\Crm\Support\EnvValueStore;

class ToolsOpenAiPlatformPageTest extends TestCase
{
    public function test_openai_platform_page_loads_with_usage_warning(): void
    {
        $service = Mockery::mock(OpenAiPlatformService::class);
        $service->shouldReceive('getDashboardData')
            ->once()
            ->andReturn([
                'hasApiKey' => true,
                'hasUsageApiKey' => false,
                'keyModeLabel' => 'Standard key only',
                'identity' => [
                    'name' => 'William Beeler',
                    'email' => 'will@example.com',
                    'role' => 'owner',
                    'organization_title' => 'Personal',
                    'has_payg_project_spend_limit' => true,
                ],
                'costs' => [
                    'status' => 'forbidden',
                    'total' => null,
                    'currency' => null,
                    'message' => 'Missing scopes: api.usage.read.',
                    'range_label' => 'March 1, 2026 to March 16, 2026',
                    'daily' => [],
                ],
                'usage' => [
                    'status' => 'forbidden',
                    'requests' => null,
                    'input_tokens' => null,
                    'output_tokens' => null,
                    'message' => 'Missing scopes: api.usage.read.',
                    'range_label' => 'March 1, 2026 to March 16, 2026',
                    'daily_requests' => [],
                    'daily_tokens' => [],
                ],
            ]);

        $this->app->instance(OpenAiPlatformService::class, $service);

        $response = $this->get(route('tools.openAiPlatform'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tools/OpenAiPlatform')
            ->where('keyModeLabel', 'Standard key only')
            ->where('identity.name', 'William Beeler')
            ->where('costs.message', 'Missing scopes: api.usage.read.')
            ->where('hasUsageApiKey', false)
        );
    }

    public function test_openai_platform_page_handles_missing_key(): void
    {
        $service = Mockery::mock(OpenAiPlatformService::class);
        $service->shouldReceive('getDashboardData')
            ->once()
            ->andReturn([
                'hasApiKey' => false,
                'hasUsageApiKey' => false,
                'keyModeLabel' => 'Missing',
                'identity' => [
                    'name' => null,
                    'email' => null,
                    'role' => null,
                    'organization_title' => null,
                    'has_payg_project_spend_limit' => null,
                ],
                'costs' => [
                    'status' => 'missing_key',
                    'total' => null,
                    'currency' => null,
                    'message' => 'Add an OpenAI API key before this page can inspect account status.',
                    'range_label' => 'March 1, 2026 to March 16, 2026',
                    'daily' => [],
                ],
                'usage' => [
                    'status' => 'missing_key',
                    'requests' => null,
                    'input_tokens' => null,
                    'output_tokens' => null,
                    'message' => 'Add an OpenAI API key before this page can inspect usage trends.',
                    'range_label' => 'March 1, 2026 to March 16, 2026',
                    'daily_requests' => [],
                    'daily_tokens' => [],
                ],
            ]);

        $this->app->instance(OpenAiPlatformService::class, $service);

        $response = $this->get(route('tools.openAiPlatform'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tools/OpenAiPlatform')
            ->where('hasApiKey', false)
            ->where('keyModeLabel', 'Missing')
            ->where('identity.name', null)
        );
    }

    public function test_openai_usage_key_can_be_saved(): void
    {
        $envValueStore = Mockery::mock(EnvValueStore::class);
        $envValueStore->shouldReceive('set')
            ->once()
            ->with('OPENAI_USAGE_API_KEY', 'sk-admin-usage-key-example');

        $this->app->instance(EnvValueStore::class, $envValueStore);
        $this->mock(OpenAiPlatformService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearCache')->once();
        });

        $response = $this->post(route('tools.openAiPlatform.usageKey.store'), [
            'usage_api_key' => 'sk-admin-usage-key-example',
        ]);

        $response->assertRedirect(route('tools.openAiPlatform'));
        $response->assertSessionHas('success', 'OpenAI usage key connected successfully.');
    }

    public function test_openai_usage_key_is_required(): void
    {
        $this->mock(EnvValueStore::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('set');
        });

        $response = $this->from(route('tools.openAiPlatform'))
            ->post(route('tools.openAiPlatform.usageKey.store'), [
                'usage_api_key' => '',
            ]);

        $response->assertRedirect(route('tools.openAiPlatform'));
        $response->assertSessionHasErrors('usage_api_key');
    }

    public function test_openai_usage_key_can_be_removed(): void
    {
        $envValueStore = Mockery::mock(EnvValueStore::class);
        $envValueStore->shouldReceive('remove')
            ->once()
            ->with('OPENAI_USAGE_API_KEY');

        $this->app->instance(EnvValueStore::class, $envValueStore);
        $this->mock(OpenAiPlatformService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearCache')->once();
        });

        $response = $this->post(route('tools.openAiPlatform.usageKey.remove'));

        $response->assertRedirect(route('tools.openAiPlatform'));
        $response->assertSessionHas('success', 'OpenAI usage key removed.');
    }

    public function test_openai_dashboard_data_is_cached_for_one_hour(): void
    {
        Cache::flush();

        config()->set('services.openai.api_key', 'sk-test-standard');
        config()->set('services.openai.usage_api_key', 'sk-test-usage');
        config()->set('services.openai.organization', 'org-test');
        config()->set('services.openai.project', 'proj-test');

        $service = app(OpenAiPlatformService::class);
        $reflection = new \ReflectionClass($service);
        $cacheKeyMethod = $reflection->getMethod('cacheKey');
        $cacheKeyMethod->setAccessible(true);
        $cacheKey = $cacheKeyMethod->invoke($service);

        Cache::put($cacheKey, [
            'hasApiKey' => true,
            'hasUsageApiKey' => true,
            'keyModeLabel' => 'Usage key configured',
            'identity' => [
                'name' => 'Cached Name',
                'email' => 'cached@example.com',
                'role' => 'owner',
                'organization_title' => 'Personal',
                'has_payg_project_spend_limit' => true,
            ],
            'costs' => [
                'status' => 'available',
                'total' => 1.99,
                'currency' => 'usd',
                'message' => 'Cached cost message',
                'range_label' => 'March 1, 2026 to March 16, 2026',
                'daily' => [],
            ],
            'usage' => [
                'status' => 'available',
                'requests' => 471,
                'input_tokens' => 100,
                'output_tokens' => 200,
                'message' => 'Cached usage message',
                'range_label' => 'March 1, 2026 to March 16, 2026',
                'daily_requests' => [],
                'daily_tokens' => [],
            ],
        ], now()->addHour());

        $data = $service->getDashboardData();

        $this->assertSame('Cached Name', $data['identity']['name']);
        $this->assertSame('Cached cost message', $data['costs']['message']);
        $this->assertSame('Cached usage message', $data['usage']['message']);
    }
}
