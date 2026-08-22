<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ClearGeocodeCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::flushdb();
    }

    public function test_it_clears_every_geocode_cache_key_without_touching_others(): void
    {
        Redis::set('geocode.v1.gb.abc123', 'nominatim result');
        Redis::set('geocode.google.v1.gb.def456', 'google result');
        Redis::set('venue.index.v5', 'unrelated venue cache');

        $this->artisan('geocode:clear-cache')
            ->expectsOutputToContain('2 cached geocoding results cleared.')
            ->assertSuccessful();

        $this->assertNull(Redis::get('geocode.v1.gb.abc123'));
        $this->assertNull(Redis::get('geocode.google.v1.gb.def456'));
        $this->assertSame('unrelated venue cache', Redis::get('venue.index.v5'));
    }

    public function test_it_reports_zero_when_nothing_is_cached(): void
    {
        $this->artisan('geocode:clear-cache')
            ->expectsOutputToContain('0 cached geocoding results cleared.')
            ->assertSuccessful();
    }
}
