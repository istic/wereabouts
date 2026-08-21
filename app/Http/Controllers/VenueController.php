<?php

namespace App\Http\Controllers;

use App\Service\Google\GoogleClient;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VenueController extends Controller
{
    public function __construct(

        protected GoogleClient $googleSheet,

    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $venues = $this->googleSheet->listVenues();

        return view('venue.index', compact('venues'));
    }

    /**
     * Display a single venue.
     */
    public function show(string $slug): View
    {
        $venue = $this->googleSheet->findVenueBySlug($slug);

        if (! $venue) {
            throw new NotFoundHttpException('Venue not found.');
        }

        return view('venue.show', compact('venue'));
    }
}
