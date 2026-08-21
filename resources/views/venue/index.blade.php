@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row">
        <div class="col-md-12 alert alert-primary">
            <h1 class="text-center">Work In Progress!</h1>
            <p class="text-center">This is the first version of this project, with many things left to do.</p>
            <ul>
              <li>If you have a venue to add, please use the <a href="https://forms.gle/DVntrUyNNpKAoPzf9">submission form</a>.</li>
              <li> If you have a suggestion or bug to report, please use the <a href="https://github.com/istic/wereabouts/issues">issue tracker</a>.</li>
              <li>If you have a correction for the spreadsheet, please add it as a comment to the row on <a href="https://docs.google.com/spreadsheets/d/1p2CS7rxPWyMs7g4OHH287j6iIPGkZb3VA7VRLBr0okA">the master spreadsheet</a>.</li>
              <li>Don't see a site on the list and it's a public building? You might be able to find it through <a href="https://www.accessable.co.uk">AccessAble</a>.</li>
              <li>The spreadsheet this is based on is being maintained by Hazel Anneke Dixon.</li>
              <li>The site is being run by Aquarion</li>
            </ul>
        </div>
</div>
    <div class="row justify-content-left">
      @foreach ($venues as $venue)
        <div class="col-md-12 mb-4 col-lg-6 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <a href="{{ route('venue.show', $venue->slug) }}">{{ $venue->name }}</a>
                </div>

                <div class="card-body">
                    @include('venue._details', ['venue' => $venue])
                </div>
            </div>
        </div>
      @endforeach
    </div>
</div>
@endsection
