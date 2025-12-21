<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Résultats {{ $race->name }}</title>
    <style>
        @page {
            margin: 1cm 0.8cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7pt;
            color: #000;
            line-height: 1.1;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e3a8a;
        }

        .header h1 {
            font-size: 16pt;
            color: #1e3a8a;
            margin: 0 0 2px 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 13pt;
            color: #3B82F6;
            margin: 0 0 4px 0;
            font-weight: normal;
        }

        .header-info {
            font-size: 7pt;
            color: #666;
            margin: 2px 0;
        }

        .category-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 15px 0 6px 0;
            padding: 3px 6px;
            background-color: #f0f4ff;
            border-left: 3px solid #3B82F6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th {
            background-color: #1e3a8a;
            color: white !important;
            padding: 4px 2px;
            text-align: left;
            font-size: 6.5pt;
            font-weight: bold;
            border: 1px solid #1e3a8a;
        }

        td {
            padding: 2px 2px;
            border: 1px solid #ddd;
            font-size: 6.5pt;
            line-height: 1.05;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .pos {
            text-align: center;
            width: 28px;
        }

        th.pos {
            font-weight: bold;
        }

        td.pos {
            font-weight: bold;
            color: #1e3a8a;
        }

        .bib {
            font-weight: bold;
            text-align: center;
            width: 32px;
        }

        .name {
            font-weight: 600;
        }

        .time {
            font-weight: bold;
            text-align: center;
            width: 50px;
        }

        .speed {
            text-align: center;
            width: 48px;
        }

        .category {
            text-align: center;
            width: 55px;
            font-size: 6.5pt;
        }

        .status {
            text-align: center;
            width: 32px;
            font-size: 6pt;
            font-weight: bold;
        }

        .status-v {
            background-color: #10B981;
            color: white;
        }

        .status-dns {
            background-color: #F59E0B;
            color: white;
        }

        .status-dnf {
            background-color: #EF4444;
            color: white;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 6pt;
            color: #999;
            padding-top: 4px;
            border-top: 1px solid #ddd;
        }

        .page-break {
            page-break-after: always;
        }

        .total-participants {
            text-align: right;
            font-size: 7pt;
            color: #666;
            margin-bottom: 2px;
            font-style: italic;
        }
    </style>
    @if($autoPrint ?? false)
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    @endif
</head>
<body>
    <div class="header">
        <h1>{{ $race->event->name ?? 'Événement' }}</h1>
        <h2>{{ $race->name }}</h2>
        <div class="header-info">
            <strong>Date d'édition :</strong> {{ now()->format('d/m/Y à H:i') }}
            @if($race->distance)
                | <strong>Distance :</strong> {{ $race->distance }} km
            @endif
        </div>
    </div>

    @if($displayMode === 'general')
        <!-- Classement Général -->
        <div class="total-participants">{{ $results->count() }} participant(s)</div>

        @php
            $isMultiLap = in_array($race->type, ['n_laps', 'infinite_loop']);
            $maxLaps = $isMultiLap && $race->laps > 0 ? $race->laps : 0;
        @endphp

        <table>
            <thead>
                <tr>
                    <th class="pos">Pos.</th>
                    <th class="bib">Dos.</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th style="width: 22px; text-align: center;">Sexe</th>
                    <th class="category">Catégorie</th>
                    <th>Club</th>
                    @if($isMultiLap && $maxLaps > 0)
                        @for($i = 1; $i <= $maxLaps; $i++)
                            <th class="time" style="width: 42px;">T{{ $i }}</th>
                        @endfor
                    @endif
                    <th class="time">Temps</th>
                    <th class="speed">Vitesse</th>
                    <th class="pos">Pos. Cat.</th>
                    <th class="status">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $index => $result)
                    <tr>
                        <td class="pos">{{ $result->position ?? '-' }}</td>
                        <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                        <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                        <td>{{ $result->entrant->firstname ?? '' }}</td>
                        <td style="text-align: center;">{{ $result->entrant->gender ?? '' }}</td>
                        <td class="category">{{ $result->entrant->category->name ?? 'N/A' }}</td>
                        <td>{{ $result->entrant->club ?? '-' }}</td>
                        @if($isMultiLap && $maxLaps > 0)
                            @php
                                $entrantLaps = $lapsByEntrant[$result->entrant_id] ?? collect();
                            @endphp
                            @for($i = 1; $i <= $maxLaps; $i++)
                                @php
                                    $lap = $entrantLaps->firstWhere('lap_number', $i);
                                    if ($lap && $lap->lap_time) {
                                        $hours = floor($lap->lap_time / 3600);
                                        $minutes = floor(($lap->lap_time % 3600) / 60);
                                        $seconds = floor($lap->lap_time % 60);
                                        $lapTime = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                                    } else {
                                        $lapTime = '-';
                                    }
                                @endphp
                                <td class="time" style="font-size: 5.5pt;">{{ $lapTime }}</td>
                            @endfor
                        @endif
                        <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                        <td class="speed">{{ $result->speed ? number_format($result->speed, 2) : '-' }}</td>
                        <td class="pos">{{ $result->category_position ?? '-' }}</td>
                        <td class="status status-{{ strtolower($result->status) }}">{{ $result->status }}</td>
                    </tr>
                    @if(($index + 1) % 50 === 0 && !$loop->last)
                        </tbody>
                        </table>
                        <div class="page-break"></div>
                        <table>
                            <thead>
                                <tr>
                                    <th class="pos">Pos.</th>
                                    <th class="bib">Dos.</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th style="width: 22px; text-align: center;">Sexe</th>
                                    <th class="category">Catégorie</th>
                                    <th>Club</th>
                                    @if($isMultiLap && $maxLaps > 0)
                                        @for($i = 1; $i <= $maxLaps; $i++)
                                            <th class="time" style="width: 42px;">T{{ $i }}</th>
                                        @endfor
                                    @endif
                                    <th class="time">Temps</th>
                                    <th class="speed">Vitesse</th>
                                    <th class="pos">Pos. Cat.</th>
                                    <th class="status">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                    @endif
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Classement par Catégorie -->
        @php
            $isMultiLap = in_array($race->type, ['n_laps', 'infinite_loop']);
            $maxLaps = $isMultiLap && $race->laps > 0 ? $race->laps : 0;
        @endphp

        @foreach($resultsByCategory as $categoryName => $categoryResults)
            <div class="category-title">
                {{ $categoryName }} - {{ $categoryResults->count() }} participant(s)
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="pos">Pos. Cat.</th>
                        <th class="pos">Pos. Gén.</th>
                        <th class="bib">Dos.</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Club</th>
                        @if($isMultiLap && $maxLaps > 0)
                            @for($i = 1; $i <= $maxLaps; $i++)
                                <th class="time" style="width: 42px;">T{{ $i }}</th>
                            @endfor
                        @endif
                        <th class="time">Temps</th>
                        <th class="speed">Vitesse</th>
                        <th class="status">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryResults as $result)
                        <tr>
                            <td class="pos">{{ $result->category_position ?? '-' }}</td>
                            <td class="pos">{{ $result->position ?? '-' }}</td>
                            <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                            <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                            <td>{{ $result->entrant->firstname ?? '' }}</td>
                            <td>{{ $result->entrant->club ?? '-' }}</td>
                            @if($isMultiLap && $maxLaps > 0)
                                @php
                                    $entrantLaps = $lapsByEntrant[$result->entrant_id] ?? collect();
                                @endphp
                                @for($i = 1; $i <= $maxLaps; $i++)
                                    @php
                                        $lap = $entrantLaps->firstWhere('lap_number', $i);
                                        if ($lap && $lap->lap_time) {
                                            $hours = floor($lap->lap_time / 3600);
                                            $minutes = floor(($lap->lap_time % 3600) / 60);
                                            $seconds = floor($lap->lap_time % 60);
                                            $lapTime = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                                        } else {
                                            $lapTime = '-';
                                        }
                                    @endphp
                                    <td class="time" style="font-size: 5.5pt;">{{ $lapTime }}</td>
                                @endfor
                            @endif
                            <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                            <td class="speed">{{ $result->speed ? number_format($result->speed, 2) : '-' }}</td>
                            <td class="status status-{{ strtolower($result->status) }}">{{ $result->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if(!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    @endif

    <div class="footer">
        ChronoFront - ATS Sport | Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
