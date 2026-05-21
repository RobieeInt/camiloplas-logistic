<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Camiloplas Logistic</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-light">

<div class="d-flex">
    @include('layouts.sidebar')

    <main class="flex-grow-1">
        @include('layouts.navbar')

        <div class="container-fluid p-4">
            {{ $slot }}
        </div>
    </main>
</div>

@livewireScripts
</body>
</html>
