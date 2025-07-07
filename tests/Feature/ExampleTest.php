<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use Mockery;

use Mockery\MockInterface;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        app()->bind(GoogleClient::class, function () { // not a service provider but the target of service provider
            return new FakeGoogleClient();
        });
    }

    function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

class FakeGoogleClient extends GoogleClient
{
    protected $client;
    protected $sheetID;

    public function __construct()
    {
        $this->client = Mockery::mock(GoogleClient::class);
        $this->sheetID = 'fake_sheet_id';
    }

    protected function getSheet()
    {
        return Mockery::mock(\Google\Service\Sheets::class);
    }

    protected function getSheetData()
    {
        $data = [
            [
                "Venue Name" => "Abney Scout and Guide Centre",
                "Location" => "Cheadle, nr Stockport",
                "Capacity" => "sleeps 32",
                "Types of Spaces" => "Indoor bunks, hall, firepit",
                "Public Transport" => "Yes",
                "Step free access" => "All spaces",
                "Disabled bathrooms?" => "No",
                "Internet?" => "Good mobile data coverage",
                "Kitchen" => "",
                "Issues" => "No mould but the bedrooms are super dusty and the air quality is v low. Would not surprise me if there was black mould or similar hiding under beds.",
                "Further description of indoor spaces" => "",
                "Aspects" => "",
                "Price data (cost + data of recorded cost)" => "£570 for two nights, extra £50 for someone to clean it"
            ]
        ];
        return [array_keys($data[0]), [array_values($data[0])]];
    }
}
