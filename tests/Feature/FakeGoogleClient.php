<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use Google\Service\Sheets;
use Mockery;

class FakeGoogleClient extends GoogleClient
{
    /**
     * @param  array<int, array<int, string>>  $rows
     * @param  array<int, ?string>  $nameHyperlinks  Keyed to match $rows, simulating
     *                                               the Venue Name cell being linked
     *                                               to a website rather than showing
     *                                               the URL as text.
     */
    public function __construct(public array $rows = [], public array $nameHyperlinks = [])
    {
        $this->sheetID = 'fake_sheet_id';
    }

    protected function getSheet(): Sheets
    {
        return Mockery::mock(Sheets::class);
    }

    protected function getSheetData(): array
    {
        $headings = [
            'Venue Name',
            'Location',
            'Capacity',
            'Types of Spaces',
            'Public Transport',
            'Step free access',
            'Disabled bathrooms?',
            'Internet?',
            'Kitchen',
            'Issues',
            'Further description of indoor spaces',
            'Aspects',
            'Price data (cost + data of recorded cost)',
        ];

        return [$headings, $this->rows, $this->nameHyperlinks];
    }
}
