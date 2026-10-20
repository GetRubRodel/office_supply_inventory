<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BAC Resolution</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header .subtitle {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 0;
        }
        .header .resolution-no {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 14px;
            text-transform: uppercase;
        }
        .body-text .title-block {
            margin: 0 0 10px 0;
            text-indent: 0;
            text-align: center;
            /* Regular weight — the title and the Division inserted into its
               blank must not be bold. */
            font-weight: normal;
            font-size: 10pt;
        }
        .body-text .title-spacer {
            margin: 0;
            text-indent: 0;
            font-weight: normal;
        }

        .hr-light {
            border: none;
            border-top: 1px solid #000;
            margin: 6px 0;
        }

        .body-text {
            text-align: justify;
            font-size: 10pt;
            line-height: 1.6;
        }
        .body-text p {
            margin: 6px 0;
            text-indent: 30px;
        }
        .body-text .clause {
            margin: 8px 0;
            text-indent: 0;
        }
        .body-text .whereas {
            margin: 8px 0 8px 20px;
            text-indent: -20px;
        }
        .body-text .whereas::before {
            content: "";
        }
        .body-text .item {
            margin: 8px 0 8px 30px;
            text-indent: -30px;
        }
        /* The closing statement is two paragraphs separated by a blank line:
           "SO RESOLVED." then "Done this <date>, City of Koronadal,
           Philippines." pre-line preserves that blank line, which would
           otherwise collapse in HTML. */
        .body-text .closing {
            margin-top: 10px;
            text-indent: 0;
            text-align: left;
            white-space: pre-line;
            font-weight: normal;
        }
        .body-text .resolved-clause {
            margin: 8px 0 8px 20px;
            text-indent: -20px;
            font-style: italic;
        }

        .signatures {
            margin-top: 30px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            text-align: center;
            padding: 5px 10px;
            vertical-align: top;
        }
        .signature-table .name {
            font-weight: bold;
            text-transform: uppercase;
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 40px;
            display: inline-block;
            min-width: 140px;
        }
        .signature-table .designation {
            font-size: 8.5pt;
            margin-top: 1px;
        }

        .approved-by {
            margin-top: 40px;
        }
        .approved-by .label {
            font-weight: bold;
            font-size: 10pt;
        }
        .approved-by .name {
            font-weight: bold;
            text-transform: uppercase;
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 50px;
            display: inline-block;
            min-width: 260px;
        }
        .approved-by .designation {
            font-size: 9pt;
            margin-top: 2px;
        }
    </style>
</head>
@php
    $logoPath = public_path('images/logo.png');
    // Canonical resolution blocks with the Division, Budget Amount and the
    // date selected on the DateTimePicker already substituted into every
    // blank. Shared with the form/view preview so the printed (and downloaded
    // PDF) wording always matches the form exactly — including the
    // "Done this ..., City of Koronadal, Philippines." closing line.
    $blocks = $bac->getResolutionBlocks();
@endphp
<body>

    {{-- ═══ HEADER ═══ --}}
    <div class="header">
        @if(file_exists($logoPath))
            <div style="text-align:center;margin-bottom:8px;">
                <picture>
                    <source srcset="{{ asset('images/logo.avif') }}" type="image/avif">
                    <source srcset="{{ asset('images/logo.webp') }}" type="image/webp">
                    <img src="{{ asset('images/logo.png') }}" alt="System Logo" width="70" height="70" style="height:70px;width:auto;" decoding="async">
                </picture>
            </div>
        @endif
        <h1>BIDS AND AWARDS COMMITTEE</h1>
        <div class="subtitle">(BAC)</div>

        <div class="resolution-no">
            {{ $bac->getDisplayResolutionNo() }}
        </div>
    </div>

    <hr class="hr-light">

    {{-- ═══ BODY TEXT ═══ --}}
    <div class="body-text">
        @foreach ($blocks as $block)
            @if ($block['type'] === 'title')
                <p class="title-block">{{ $block['text'] }}</p>
                {{-- One blank line between the title and the WHEREAS clauses. --}}
                <p class="title-spacer" aria-hidden="true">&nbsp;</p>
            @elseif ($block['type'] === 'whereas')
                <p class="whereas">{{ $block['text'] }}</p>
            @elseif ($block['type'] === 'item')
                <p class="item">{{ $block['text'] }}</p>
            @elseif ($block['type'] === 'closing')
                <p class="closing">{{ $block['text'] }}</p>
            @else
                <p>{{ $block['text'] }}</p>
            @endif
        @endforeach
    </div>

    {{-- ═══ BAC MEMBERS ═══ --}}
    <div class="signatures">
        <table class="signature-table">
            <tr>
                <td style="width:100%;" colspan="5">
                    <div class="name">{{ $bac->chairperson_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->chairperson_designation ?: '________________________' }}</div>
                </td>
            </tr>
            <tr>
                <td style="width:33%;">
                    <div class="name">{{ $bac->vice_chairperson_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->vice_chairperson_designation ?: '________________________' }}</div>
                </td>
                <td style="width:34%;">
                    <div class="name">{{ $bac->member1_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->member1_designation ?: '________________________' }}</div>
                </td>
                <td style="width:33%;">
                    <div class="name">{{ $bac->member2_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->member2_designation ?: '________________________' }}</div>
                </td>
            </tr>
            <tr>
                <td style="width:50%;">
                    <div class="name">{{ $bac->member3_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->member3_designation ?: '________________________' }}</div>
                </td>
                <td style="width:50%;"></td>
            </tr>
        </table>
    </div>

    {{-- ═══ APPROVED BY ═══ --}}
    <div class="approved-by">
        <div class="label">Approved by:</div>
        <div class="name">{{ $bac->approved_by_name ?: '________________________' }}</div>
        <div class="designation">{{ $bac->approved_by_designation ?: 'Head of the Procuring Entity' }}</div>
    </div>

</body>
</html>
