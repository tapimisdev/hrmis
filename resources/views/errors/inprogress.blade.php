<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS - Page Unavailable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
     @vite([
        'resources/sass/app.scss', 
        'resources/js/app.js',
        'resources/sass/underconstruction.scss',
    ])
</head>
<body>

    <div class="card">
        <img src="{{ asset('/img/orbit_circle.png') }}" alt="HRIS Logo" class="hris-logo">

        <h1>Oops!</h1>
        <p>This page isn’t available at the moment. Our HRIS system is working hard to serve you better.</p>
        <a href="{{ url('/employee/dashboard') }}" class="btn btn-primary shadow">Go Back to Dashboard</a>
    </div>

</body>
</html>
