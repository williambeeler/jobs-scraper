<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ('CRM for ' . config('app.name')) }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    @stack('styles')
</head>
<body>

    <main class="main-content">

        <div class="container-fluid p-5">
            @yield('content')
        </div>

        <footer class="bg-light py-3 mt-5">
            <div class="container text-center">
                <small>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</small>
            </div>
        </footer>

    </main>

    @stack('scripts')
    
</body>
</html>
