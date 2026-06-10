<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>BIR 2316 PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; color: #111827; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .subtitle { font-size: 12px; color: #4b5563; }
        .grid { width: 100%; margin-bottom: 16px; }
        .col { width: 48%; display: inline-block; vertical-align: top; margin-right: 2%; }
        .card { border: 1px solid #d1d5db; padding: 12px; margin-bottom: 12px; }
        .card h2 { font-size: 13px; margin: 0 0 8px; text-transform: uppercase; }
        .row { margin-bottom: 7px; font-size: 11px; }
        .label { color: #6b7280; display: block; margin-bottom: 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; font-size: 11px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    @include('admin.pages.taxation.bir-2316.partials.form', ['record' => $record])
</body>
</html>
