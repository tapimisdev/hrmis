<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>

    <!-- Vite (compiles and loads local SCSS/JS assets) -->
    @vite([
        'resources/sass/errors.scss',
    ])
</head>
<body>
    <div class="container-fluid">
        @yield('content')
    </div>
</body>
</html>