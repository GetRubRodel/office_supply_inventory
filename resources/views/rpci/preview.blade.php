<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPCI Preview - {{ $rpci->report_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #e5e7eb;
            color: #111827;
        }

        /* ─── TOP TOOLBAR ─────────────────────────── */
        .toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #1f2937;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .toolbar-title {
            font-size: 16px;
            font-weight: 600;
        }
        .toolbar-title span {
            color: #93c5fd;
        }
        .toolbar-actions {
            display: flex;
            gap: 10px;
        }
        .toolbar-actions button, .toolbar-actions a {
            padding: 8px 20px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        .btn-pdf {
            background: #059669;
            color: #fff;
        }
        .btn-pdf:hover {
            background: #047857;
        }
        .btn-close {
            background: #6b7280;
            color: #fff;
        }
        .btn-close:hover {
            background: #4b5563;
        }

        /* ─── DOCUMENT CONTAINER ──────────────────── */
        .document-wrapper {
            margin-top: 60px;
            padding: 30px 20px;
            display: flex;
            justify-content: center;
        }
        .document-container {
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 12mm 15mm;
            width: 330mm;
            max-width: 100%;
            overflow: auto;
        }

        /* ─── PRINT OVERRIDES ─────────────────────── */
        @media print {
            .toolbar {
                display: none !important;
            }
            .document-wrapper {
                margin-top: 0;
                padding: 0;
            }
            .document-container {
                box-shadow: none;
                padding: 0;
                width: 100%;
            }
            body {
                background: #fff;
            }
        }
    </style>
</head>
<body>
    {{-- Toolbar --}}
    <div class="toolbar">
        <div class="toolbar-title">
            RPCI Preview: <span>{{ $rpci->report_no }}</span>
        </div>
        <div class="toolbar-actions">
            <button class="btn-print" onclick="window.print()">
                🖨️ Print
            </button>
            <a href="{{ route('rpci.pdf', $rpci) }}" class="btn-pdf" target="_blank">
                📄 Export PDF
            </a>
            <button class="btn-close" onclick="window.close()">
                ✕ Close
            </button>
        </div>
    </div>

    {{-- RPCI Content --}}
    <div class="document-wrapper">
        <div class="document-container">
            @include('rpci.print', ['rpci' => $rpci])
        </div>
    </div>

    <script>
        // Auto-trigger print if ?print=1 is in URL
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.onload = function() {
                setTimeout(function() { window.print(); }, 500);
            };
        }
    </script>
</body>
</html>
