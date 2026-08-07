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
            font-size: 10pt;
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
        .header .title {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 12px;
            font-style: italic;
            text-transform: uppercase;
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

        .dateline {
            font-size: 10pt;
            margin: 16px 0;
            text-align: left;
        }
    </style>
</head>
<body>

    {{-- ═══ HEADER ═══ --}}
    <div class="header">
        @php
            $logoPath = public_path('images/logo.png');
        @endphp
        @if(file_exists($logoPath))
            <div style="text-align:center;margin-bottom:8px;">
                <img src="{{ asset('images/logo.png') }}" alt="System Logo" style="height:70px;width:auto;">
            </div>
        @endif
        <h1>BIDS AND AWARDS COMMITTEE</h1>
        <div class="subtitle">(BAC)</div>

        <div class="resolution-no">
            {{ $bac->getDisplayResolutionNo() }}
        </div>

        @if($bac->title)
            <div class="hr-light"></div>
            <div class="title">{{ $bac->title }}</div>
        @endif
    </div>

    <hr class="hr-light">

    {{-- ═══ BODY TEXT ═══ --}}
    <div class="body-text">
        @if($bac->preamble)
            @foreach(explode("\n\n", $bac->preamble) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        @endif

        @if($bac->operative_part)
            <p>{{ $bac->operative_part }}</p>
        @endif

        @if($bac->further_resolved)
            <p>{{ $bac->further_resolved }}</p>
        @endif

        @if($bac->closing)
            <p>{{ $bac->closing }}</p>
        @endif
    </div>

    {{-- ═══ DATELINE ═══ --}}
    @if($bac->date || $bac->place)
        <div class="dateline">
            Done this {{ $bac->date ? \Carbon\Carbon::parse($bac->date)->format('jS \d\a\y \o\f F Y') : '________________' }},
            {{ $bac->place ?? '________________' }}.
        </div>
    @endif

    {{-- ═══ BAC MEMBERS ═══ --}}
    <div class="signatures">
        <table class="signature-table">
            <tr>
                <td style="width:100%;" colspan="5">
                    <div class="name">{{ $bac->chairperson_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->chairperson_designation ?: 'Chairperson' }}</div>
                </td>
            </tr>
            <tr>
                <td style="width:33%;">
                    <div class="name">{{ $bac->vice_chairperson_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->vice_chairperson_designation ?: 'Vice-Chairperson' }}</div>
                </td>
                <td style="width:34%;">
                    <div class="name">{{ $bac->member1_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->member1_designation ?: 'Member' }}</div>
                </td>
                <td style="width:33%;">
                    <div class="name">{{ $bac->member2_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->member2_designation ?: 'Member' }}</div>
                </td>
            </tr>
            <tr>
                <td style="width:50%;">
                    <div class="name">{{ $bac->member3_name ?: '________________________' }}</div>
                    <div class="designation">{{ $bac->member3_designation ?: 'Member' }}</div>
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
