<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class LayoutIconsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::del('venue.index.v5', 'venue.index.v5.stale');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_the_layout_head_links_the_generated_favicons_and_manifest(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([]));

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('rel="icon"', false);
        $response->assertSee('rel="shortcut icon"', false);
        $response->assertSee('rel="apple-touch-icon"', false);
        $response->assertSee('rel="manifest"', false);
    }

    public function test_the_navbar_brand_shows_the_favicon(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([]));

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['navbar-brand', 'favicon', config('app.name')], false);
    }
}
