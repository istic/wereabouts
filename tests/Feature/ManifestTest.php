<?php

namespace Tests\Feature;

use Tests\TestCase;

class ManifestTest extends TestCase
{
    public function test_the_web_manifest_is_served_with_the_correct_content_type(): void
    {
        $response = $this->get('/site.webmanifest');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/manifest+json');
    }

    public function test_the_web_manifest_reports_the_app_name_and_theme_color(): void
    {
        $response = $this->get('/site.webmanifest');

        $response->assertJsonPath('name', config('app.name'));
        $response->assertJsonPath('short_name', config('app.name'));
        $response->assertJsonPath('display', 'standalone');
        $response->assertJsonPath('theme_color', config('branding.default_color'));
        $response->assertJsonPath('background_color', config('branding.default_color'));
    }

    public function test_the_web_manifest_lists_both_icon_sizes(): void
    {
        $response = $this->get('/site.webmanifest');

        $response->assertJsonCount(2, 'icons');
        $response->assertJsonPath('icons.0.sizes', '192x192');
        $response->assertJsonPath('icons.1.sizes', '512x512');
    }
}
