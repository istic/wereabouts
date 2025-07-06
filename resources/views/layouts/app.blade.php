<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        <li class="nav-item">
                            <a class="nav-link" href="https://docs.google.com/spreadsheets/d/1p2CS7rxPWyMs7g4OHH287j6iIPGkZb3VA7VRLBr0okA">View Spreadsheet</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="https://forms.gle/DVntrUyNNpKAoPzf9">Submit Venue</a>
                        </li>

                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>

        <footer class="bg-white text-center text-lg-start">
            <div class="text-center p-3">
                Data brazenly stolen from <a href="https://docs.google.com/spreadsheets/d/1p2CS7rxPWyMs7g4OHH287j6iIPGkZb3VA7VRLBr0okA">Hazel's Spreadsheet</a> with permission. Submit comments for venues on the spreadsheet. <br/>
                Website version by Aquarion, Submit website bugs or feature requests on <a href="https://github.com/istic/wereabouts/issues">Github</a>
            </div>
        </footer>
    </div>
</body>
</html>
