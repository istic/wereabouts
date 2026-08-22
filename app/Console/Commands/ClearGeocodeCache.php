<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

/**
 * Every geocoder's cache keys live under a shared "geocode." prefix
 * (NominatimGeocoder: geocode.v1.*, GoogleGeocoder: geocode.google.v1.*)
 * specifically so they can all be cleared in one action here, without
 * needing direct Redis access.
 */
class ClearGeocodeCache extends Command
{
    protected $signature = 'geocode:clear-cache';

    protected $description = 'Clear every cached geocoding result, so venue locations are looked up fresh next time a map is loaded';

    public function handle(): int
    {
        // Redis::keys() returns keys already carrying the configured
        // connection prefix (config('database.redis.options.prefix')),
        // but Redis::del() applies that prefix itself - passing keys()'s
        // results straight to del() would double-prefix them and silently
        // delete nothing, so it has to be stripped first.
        $prefix = config('database.redis.options.prefix', '');

        $keys = array_map(
            fn (string $key) => str_starts_with($key, $prefix) ? substr($key, strlen($prefix)) : $key,
            Redis::keys('geocode.*'),
        );

        if ($keys) {
            Redis::del(...$keys);
        }

        $this->info(count($keys).' cached geocoding '.(count($keys) === 1 ? 'result' : 'results').' cleared.');

        return self::SUCCESS;
    }
}
