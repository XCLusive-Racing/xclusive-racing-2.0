<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XCLusive Racing - The Lion is Born to Dominate')</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>

@include('layouts._navbar')

@yield('content')

@include('layouts._footer')

</body>
</html>