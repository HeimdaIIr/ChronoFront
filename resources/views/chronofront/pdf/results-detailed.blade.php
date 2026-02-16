<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Résultats Détaillés {{ $race->name }}</title>
    <style>
        @page {
            margin: 0.8cm 0.5cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 6pt;
            color: #000;
            line-height: 1.05;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #1e3a8a;
        }

        .header h1 {
            font-size: 13pt;
            color: #1e3a8a;
            margin: 0 0 2px 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 10pt;
            color: #3B82F6;
            margin: 0 0 3px 0;
            font-weight: normal;
        }

        .header-info {
            font-size: 6pt;
            color: #666;
            margin: 1px 0;
        }

        .category-title {
            font-size: 9pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 10px 0 5px 0;
            padding: 2px 5px;
            background-color: #f0f4ff;
            border-left: 3px solid #3B82F6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th {
            background-color: #1e3a8a;
            color: white !important;
            padding: 3px 1px;
            text-align: left;
            font-size: 5.5pt;
            font-weight: bold;
            border: 1px solid #1e3a8a;
        }

        td {
            padding: 1.5px 1px;
            border: 1px solid #ddd;
            font-size: 5.5pt;
            line-height: 1.0;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr {
            page-break-inside: avoid;
        }

        .pos {
            text-align: center;
            width: 20px;
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
            width: 25px;
        }

        .name {
            font-weight: 600;
            width: 80px;
        }

        .firstname {
            width: 70px;
        }

        .time {
            font-weight: bold;
            text-align: center;
            width: 38px;
        }

        .lap-time {
            text-align: center;
            font-size: 5pt;
            width: 30px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 5pt;
            color: #999;
            padding-top: 3px;
            border-top: 1px solid #ddd;
        }

        .page-break {
            page-break-after: always;
        }

        .total-participants {
            text-align: right;
            font-size: 6pt;
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
    @php
        $runnersPerPage = 50;
        $maxLapsPerPage = 20;

        // Build lap column chunks for horizontal pagination
        // $isMultiLap and $maxLaps come from the controller
        $lapColumnChunks = [];
        if ($isMultiLap && $maxLaps > 0) {
            for ($s = 1; $s <= $maxLaps; $s += $maxLapsPerPage) {
                $e = min($s + $maxLapsPerPage - 1, $maxLaps);
                $lapColumnChunks[] = ['start' => $s, 'end' => $e];
            }
        }
        $hasLapColumns = !empty($lapColumnChunks);
        $multipleLapPages = count($lapColumnChunks) > 1;
    @endphp

    @if($displayMode === 'general')
        {{-- ===== CLASSEMENT GÉNÉRAL ===== --}}
        @php
            $runnerChunks = $results->chunk($runnersPerPage);
            $isFirstPage = true;
        @endphp

        @foreach($runnerChunks as $runnerChunk)
            @if($hasLapColumns)
                @foreach($lapColumnChunks as $lapRange)
                    @if(!$isFirstPage)
                        <div class="page-break"></div>
                    @endif
                    @php $isFirstPage = false; @endphp

                    <div class="header">
                        <h1>{{ $race->event->name ?? 'Événement' }}</h1>
                        <h2>{{ $race->name }} - Résultats Détaillés</h2>
                        <div class="header-info">
                            <strong>Date d'édition :</strong> {{ now()->format('d/m/Y à H:i') }}
                            @if($race->distance)
                                | <strong>Distance :</strong> {{ $race->distance }} km
                            @endif
                            | <strong>Tours :</strong> {{ $maxLaps }}
                            @if($multipleLapPages)
                                (T{{ $lapRange['start'] }} à T{{ $lapRange['end'] }})
                            @endif
                        </div>
                    </div>

                    <div class="total-participants">{{ $results->count() }} participant(s)</div>

                    <table>
                        <thead>
                            <tr>
                                <th class="pos">Pos.</th>
                                <th class="bib">Dos.</th>
                                <th class="name">Nom</th>
                                <th class="firstname">Prénom</th>
                                @for($i = $lapRange['start']; $i <= $lapRange['end']; $i++)
                                    <th class="lap-time">T{{ $i }}</th>
                                @endfor
                                <th class="time">Temps Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($runnerChunk as $result)
                                <tr>
                                    <td class="pos">{{ $result->position ?? '-' }}</td>
                                    <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                                    <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                                    <td class="firstname">{{ $result->entrant->firstname ?? '' }}</td>
                                    @php
                                        $entrantLaps = $lapsByEntrant[$result->entrant_id] ?? collect();
                                    @endphp
                                    @for($i = $lapRange['start']; $i <= $lapRange['end']; $i++)
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
                                        <td class="lap-time">{{ $lapTime }}</td>
                                    @endfor
                                    <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @else
                {{-- Pas de colonnes tours (course simple) --}}
                @if(!$isFirstPage)
                    <div class="page-break"></div>
                @endif
                @php $isFirstPage = false; @endphp

                <div class="header">
                    <h1>{{ $race->event->name ?? 'Événement' }}</h1>
                    <h2>{{ $race->name }} - Résultats Détaillés</h2>
                    <div class="header-info">
                        <strong>Date d'édition :</strong> {{ now()->format('d/m/Y à H:i') }}
                        @if($race->distance)
                            | <strong>Distance :</strong> {{ $race->distance }} km
                        @endif
                    </div>
                </div>

                <div class="total-participants">{{ $results->count() }} participant(s)</div>

                <table>
                    <thead>
                        <tr>
                            <th class="pos">Pos.</th>
                            <th class="bib">Dos.</th>
                            <th class="name">Nom</th>
                            <th class="firstname">Prénom</th>
                            <th class="time">Temps Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($runnerChunk as $result)
                            <tr>
                                <td class="pos">{{ $result->position ?? '-' }}</td>
                                <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                                <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                                <td class="firstname">{{ $result->entrant->firstname ?? '' }}</td>
                                <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach

    @else
        {{-- ===== CLASSEMENT PAR CATÉGORIE ===== --}}
        @php $isFirstPage = true; @endphp

        @foreach($resultsByCategory as $categoryName => $categoryResults)
            @php
                $catRunnerChunks = $categoryResults->chunk($runnersPerPage);
            @endphp

            @foreach($catRunnerChunks as $catChunk)
                @if($hasLapColumns)
                    @foreach($lapColumnChunks as $lapRange)
                        @if(!$isFirstPage)
                            <div class="page-break"></div>
                        @endif
                        @php $isFirstPage = false; @endphp

                        <div class="header">
                            <h1>{{ $race->event->name ?? 'Événement' }}</h1>
                            <h2>{{ $race->name }} - Résultats Détaillés</h2>
                            <div class="header-info">
                                <strong>Date d'édition :</strong> {{ now()->format('d/m/Y à H:i') }}
                                @if($race->distance)
                                    | <strong>Distance :</strong> {{ $race->distance }} km
                                @endif
                                | <strong>Tours :</strong> {{ $maxLaps }}
                                @if($multipleLapPages)
                                    (T{{ $lapRange['start'] }} à T{{ $lapRange['end'] }})
                                @endif
                            </div>
                        </div>

                        <div class="category-title">
                            {{ $categoryName }} - {{ $categoryResults->count() }} participant(s)
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th class="pos">Pos. Cat.</th>
                                    <th class="pos">Pos. Gén.</th>
                                    <th class="bib">Dos.</th>
                                    <th class="name">Nom</th>
                                    <th class="firstname">Prénom</th>
                                    @for($i = $lapRange['start']; $i <= $lapRange['end']; $i++)
                                        <th class="lap-time">T{{ $i }}</th>
                                    @endfor
                                    <th class="time">Temps Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($catChunk as $result)
                                    <tr>
                                        <td class="pos">{{ $result->category_position ?? '-' }}</td>
                                        <td class="pos">{{ $result->position ?? '-' }}</td>
                                        <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                                        <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                                        <td class="firstname">{{ $result->entrant->firstname ?? '' }}</td>
                                        @php
                                            $entrantLaps = $lapsByEntrant[$result->entrant_id] ?? collect();
                                        @endphp
                                        @for($i = $lapRange['start']; $i <= $lapRange['end']; $i++)
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
                                            <td class="lap-time">{{ $lapTime }}</td>
                                        @endfor
                                        <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                @else
                    {{-- Pas de colonnes tours --}}
                    @if(!$isFirstPage)
                        <div class="page-break"></div>
                    @endif
                    @php $isFirstPage = false; @endphp

                    <div class="header">
                        <h1>{{ $race->event->name ?? 'Événement' }}</h1>
                        <h2>{{ $race->name }} - Résultats Détaillés</h2>
                        <div class="header-info">
                            <strong>Date d'édition :</strong> {{ now()->format('d/m/Y à H:i') }}
                            @if($race->distance)
                                | <strong>Distance :</strong> {{ $race->distance }} km
                            @endif
                        </div>
                    </div>

                    <div class="category-title">
                        {{ $categoryName }} - {{ $categoryResults->count() }} participant(s)
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th class="pos">Pos. Cat.</th>
                                <th class="pos">Pos. Gén.</th>
                                <th class="bib">Dos.</th>
                                <th class="name">Nom</th>
                                <th class="firstname">Prénom</th>
                                <th class="time">Temps Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($catChunk as $result)
                                <tr>
                                    <td class="pos">{{ $result->category_position ?? '-' }}</td>
                                    <td class="pos">{{ $result->position ?? '-' }}</td>
                                    <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                                    <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                                    <td class="firstname">{{ $result->entrant->firstname ?? '' }}</td>
                                    <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach
        @endforeach
    @endif

    <div class="footer">
        ChronoFront - ATS Sport | Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
