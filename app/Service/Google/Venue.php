<?php

namespace App\Service\Google;

use Illuminate\Support\Str;

readonly class Venue
{
    public function __construct(
        public string $name,
        public ?string $website,
        public ?string $location,
        public ?string $capacity,
        public int $capacityCount,
        public ?string $typesOfSpaces,
        public ?string $publicTransport,
        public ?string $stepFreeAccess,
        public ?string $disabledBathrooms,
        public ?string $internet,
        public ?string $kitchen,
        public ?string $issues,
        public ?string $furtherDescription,
        public ?string $aspects,
        public ?string $priceData,
        public bool $open,
        public string $slug,
    ) {}

    /**
     * Build a Venue from a raw spreadsheet row, keyed by the sheet's own heading text.
     *
     * @param  array<int, string>  $headings
     * @param  array<int, string>  $row
     */
    public static function fromSheetRow(array $headings, array $row, bool $open): self
    {
        $values = [];
        foreach ($headings as $index => $heading) {
            $values[$heading] = $row[$index] ?? null;
        }

        return new self(
            name: $values['Venue Name'],
            website: self::extractWebsite($values['Venue Name']),
            location: $values['Location'] ?? null,
            capacity: $values['Capacity'] ?? null,
            capacityCount: (int) filter_var($values['Capacity'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            typesOfSpaces: $values['Types of Spaces'] ?? null,
            publicTransport: $values['Public Transport'] ?? null,
            stepFreeAccess: $values['Step free access'] ?? null,
            disabledBathrooms: $values['Disabled bathrooms?'] ?? null,
            internet: $values['Internet?'] ?? null,
            kitchen: $values['Kitchen'] ?? null,
            issues: $values['Issues'] ?? null,
            furtherDescription: $values['Further description of indoor spaces'] ?? null,
            aspects: $values['Aspects'] ?? null,
            priceData: $values['Price data (cost + data of recorded cost)'] ?? null,
            open: $open,
            slug: Str::slug($values['Venue Name']),
        );
    }

    /**
     * The "Venue Name" column is sometimes filled in with a URL instead of
     * an actual name. When that happens, surface it as the venue's website.
     */
    private static function extractWebsite(?string $name): ?string
    {
        $name = trim($name ?? '');
        if (! preg_match('#^(https?://|www\.)\S+$#i', $name, $match)) {
            return null;
        }

        $prefix = $match[1];

        return (str_contains($prefix, '/') ? '' : 'http://').$name;
    }

    /**
     * Returns a copy of this Venue with a different slug, used to
     * disambiguate venues that would otherwise share the same slug.
     */
    public function withSlug(string $slug): self
    {
        return self::fromArray([...$this->toArray(), 'slug' => $slug]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }
}
