<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>BIR 2316 Print</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 24px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 24px; }
        .title { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
        .subtitle { font-size: 13px; color: #4b5563; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; }
        .card h2 { font-size: 14px; margin: 0 0 12px; text-transform: uppercase; }
        .row { margin-bottom: 8px; font-size: 13px; }
        .label { color: #6b7280; display: block; margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; font-size: 12px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body onload="window.print()">
    @include('admin.pages.taxation.bir-2316.partials.form', ['record' => $record])
</body>
</html>
