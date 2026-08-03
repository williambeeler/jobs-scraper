<?php

namespace Tests\Feature;

class NavigationTest extends FeatureTestCase
{
    public function test_public_navigation_pages_are_available(): void
    {
        $this->get(route('jobs.cron'))
            ->assertOk()
            ->assertSee('Job Scraper')
            ->assertSee('navbar navbar-expand bg-white', false)
            ->assertSee('data-bs-toggle="dropdown"', false)
            ->assertSeeInOrder(['Tools', 'Applied to', 'Settings', 'Companies List', 'Cron', 'Excluded terms'])
            ->assertSee(route('jobs.company-preferences.index'), false)
            ->assertSee('aria-label="Home"', false)
            ->assertSee('aria-label="Settings"', false);

        $this->get(route('jobs.applied-to'))
            ->assertOk()
            ->assertSee('Job Scraper')
            ->assertSee('aria-label="Jobs"', false)
            ->assertSee('aria-label="Bible"', false);

        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertSeeInOrder([
                'Backlog',
                'Interested',
                'Applied To',
                'Interview',
                'Job Won',
                'Rejected',
            ])
            ->assertSee('data-kanban-column', false);
    }

    public function test_settings_backup_page_has_backup_and_restore_navigation(): void
    {
        $this->get(route('settings.backup'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Create database backup')
            ->assertSee('Previous backups')
            ->assertSee(route('settings.restore'), false);

        $this->get(route('settings.restore'))
            ->assertOk()
            ->assertSee('Restore controls will be added here');
    }
}
