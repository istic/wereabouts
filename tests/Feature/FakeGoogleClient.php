<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use Google\Service\Sheets;
use Mockery;

class FakeGoogleClient extends GoogleClient
{
    /**
     * @param  array<int, array<int, string>>  $rows
     */
    public function __construct(public array $rows = [])
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

        return [$headings, $this->rows];
    }
}
