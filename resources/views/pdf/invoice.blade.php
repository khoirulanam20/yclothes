<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $heading }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 0;
            padding: 32px;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header h1 {
            margin: 0 0 4px;
            font-size: 22px;
            color: #111827;
        }
        .header .company {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
        }
        .header .address {
            margin: 4px 0 0;
            color: #6b7280;
            white-space: pre-line;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }
        .meta-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 2px;
        }
        .meta-value {
            font-size: 12px;
            color: #111827;
            margin-bottom: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .items-table th {
            background: #f3f4f6;
            color: #374151;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #d1d5db;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table .num { text-align: right; }
        .totals {
            margin-top: 16px;
            width: 100%;
        }
        .totals td {
            padding: 3px 0;
        }
        .totals .label { color: #6b7280; text-align: right; padding-right: 16px; }
        .totals .value { text-align: right; font-weight: 500; width: 140px; }
        .totals .grand td {
            padding-top: 8px;
            border-top: 2px solid #111827;
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }
        .footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #9ca3af;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-other { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
@include('partials.invoice-body', [
    'heading' => $heading,
    'variant' => 'pdf',
])
</body>
</html>
