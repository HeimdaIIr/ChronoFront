<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Récompenses {{ $race->name }}</title>
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
            margin-bottom: 12px;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 8px;
        }

        .header h1 {
            font-size: 16pt;
            color: #1e3a8a;
            margin: 0 0 3px 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 13pt;
            color: #f59e0b;
            margin: 0 0 5px 0;
            font-weight: bold;
        }

        .header-info {
            font-size: 7pt;
            color: #666;
            margin: 3px 0;
        }

        .config-info {
            background-color: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 6px 8px;
            margin-bottom: 12px;
            font-size: 7pt;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: white;
            background-color: #f59e0b;
            padding: 5px 8px;
            margin-top: 8px;
            margin-bottom: 5px;
        }

        .table-section {
            page-break-inside: avoid;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f59e0b;
            color: white !important;
            padding: 4px 2px;
            text-align: left;
            font-size: 6.5pt;
            font-weight: bold;
            border: 1px solid #f59e0b;
        }

        td {
            padding: 2px 2px;
            border: 1px solid #ddd;
            font-size: 6.5pt;
            line-height: 1.05;
        }

        tr:nth-child(even) {
            background-color: #fffbeb;
        }

        .pos {
            text-align: center;
            width: 40px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .bib {
            font-weight: bold;
            text-align: center;
            width: 45px;
        }

        .name {
            font-weight: 600;
        }

        .time {
            font-weight: bold;
            text-align: center;
            width: 70px;
        }

        .category {
            text-align: center;
            width: 80px;
        }

        .award-reason {
            font-size: 6.5pt;
            font-weight: normal;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #999;
            padding-top: 8px;
            border-top: 1px solid #ddd;
        }

        .total-awards {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 10px;
        }

        .page-break {
            page-break-after: always;
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
        <h2>{{ $race->name }} - RÉCOMPENSES</h2>
        <div class="header-info">
            <strong>Date d'édition :</strong> {{ now()->format('d/m/Y à H:i') }}
            @if($race->distance)
                | <strong>Distance :</strong> {{ $race->distance }} km
            @endif
        </div>
    </div>

    <!-- Configuration info -->
    <div class="config-info">
        <strong>Configuration des récompenses :</strong><br>
        @if($config['topScratch'] > 0)
            • Top {{ $config['topScratch'] }} au Scratch Général<br>
        @endif
        @if($config['topGender'] > 0)
            • Top {{ $config['topGender'] }} par Genre (Femmes / Hommes)<br>
        @endif
        @if($config['topCategory'] > 0)
            • Top {{ $config['topCategory'] }} par Catégorie<br>
        @endif
        @if($config['topGenderCategory'] > 0)
            • Top {{ $config['topGenderCategory'] }} par Genre ET Catégorie<br>
        @endif
    </div>

    <div class="total-awards">
        {{ $results->count() }} RÉCOMPENSES AU TOTAL
    </div>

    <!-- SCRATCH GÉNÉRAL -->
    @if($scratchResults->isNotEmpty())
    <div class="table-section">
        <div class="section-title">CLASSEMENT SCRATCH GÉNÉRAL</div>
        <table>
        <thead>
            <tr>
                <th class="pos">Pos.</th>
                <th class="bib">Dos.</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th style="width: 30px; text-align: center;">Sexe</th>
                <th class="category">Catégorie</th>
                <th>Club</th>
                <th class="time">Temps</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scratchResults as $result)
                <tr>
                    <td class="pos">{{ $result->position ?? '-' }}</td>
                    <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                    <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                    <td>{{ $result->entrant->firstname ?? '' }}</td>
                    <td style="text-align: center;">{{ $result->entrant->gender ?? '' }}</td>
                    <td class="category">{{ $result->entrant->category->name ?? 'N/A' }}</td>
                    <td>{{ $result->entrant->club ?? '-' }}</td>
                    <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
        </table>
    </div>
    @endif

    <!-- PAR GENRE -->
    @if($genderResults->isNotEmpty())
    <div class="table-section">
        <div class="section-title">CLASSEMENT PAR GENRE</div>
        <table>
        <thead>
            <tr>
                <th class="pos">Pos.</th>
                <th class="bib">Dos.</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th style="width: 30px; text-align: center;">Sexe</th>
                <th class="category">Catégorie</th>
                <th>Club</th>
                <th class="time">Temps</th>
                <th>Récompense</th>
            </tr>
        </thead>
        <tbody>
            @foreach($genderResults as $result)
                <tr>
                    <td class="pos">{{ $result->position ?? '-' }}</td>
                    <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                    <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                    <td>{{ $result->entrant->firstname ?? '' }}</td>
                    <td style="text-align: center;">{{ $result->entrant->gender ?? '' }}</td>
                    <td class="category">{{ $result->entrant->category->name ?? 'N/A' }}</td>
                    <td>{{ $result->entrant->club ?? '-' }}</td>
                    <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                    <td><span class="award-reason">{{ $result->award_reason ?? '' }}</span></td>
                </tr>
            @endforeach
        </tbody>
        </table>
    </div>
    @endif

    <!-- PAR CATÉGORIE -->
    @if($categoryResults->isNotEmpty())
        <div class="section-title">CLASSEMENT PAR CATÉGORIE</div>
        @php
            $byCategory = $categoryResults->groupBy('entrant.category.name');
        @endphp
        @foreach($byCategory as $categoryName => $catResults)
            <div class="table-section">
                <div style="font-size: 7.5pt; font-weight: bold; margin-top: 8px; margin-bottom: 3px; color: #1e3a8a;">{{ $categoryName }}</div>
                <table>
                <thead>
                    <tr>
                        <th class="pos">Pos.</th>
                        <th class="bib">Dos.</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th style="width: 30px; text-align: center;">Sexe</th>
                        <th>Club</th>
                        <th class="time">Temps</th>
                        <th>Récompense</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($catResults as $result)
                        <tr>
                            <td class="pos">{{ $result->position ?? '-' }}</td>
                            <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                            <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                            <td>{{ $result->entrant->firstname ?? '' }}</td>
                            <td style="text-align: center;">{{ $result->entrant->gender ?? '' }}</td>
                            <td>{{ $result->entrant->club ?? '-' }}</td>
                            <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                            <td><span class="award-reason">{{ $result->award_reason ?? '' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
                </table>
            </div>
        @endforeach
    @endif

    <!-- PAR GENRE ET CATÉGORIE -->
    @if($genderCategoryResults->isNotEmpty())
        <div class="section-title">CLASSEMENT PAR GENRE ET CATÉGORIE</div>
        @php
            // Grouper d'abord par genre, puis par catégorie
            $byGender = $genderCategoryResults->groupBy('entrant.gender');
        @endphp

        @foreach(['F', 'M'] as $gender)
            @if(isset($byGender[$gender]) && $byGender[$gender]->isNotEmpty())
                <!-- Titre genre -->
                <div style="font-size: 8pt; font-weight: bold; margin-top: 10px; margin-bottom: 5px; color: #f59e0b; text-transform: uppercase;">
                    {{ $gender === 'F' ? 'FEMMES' : 'HOMMES' }}
                </div>

                @php
                    $genderResults = $byGender[$gender];
                    $byCategory = $genderResults->groupBy('entrant.category.name');
                @endphp

                @foreach($byCategory as $categoryName => $catResults)
                    <div class="table-section">
                        <div style="font-size: 7.5pt; font-weight: bold; margin-top: 8px; margin-bottom: 3px; color: #1e3a8a;">{{ $categoryName }}</div>
                        <table>
                        <thead>
                            <tr>
                                <th class="pos">Pos.</th>
                                <th class="bib">Dos.</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Club</th>
                                <th class="time">Temps</th>
                                <th>Récompense</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($catResults as $result)
                                <tr>
                                    <td class="pos">{{ $result->position ?? '-' }}</td>
                                    <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                                    <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                                    <td>{{ $result->entrant->firstname ?? '' }}</td>
                                    <td>{{ $result->entrant->club ?? '-' }}</td>
                                    <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                                    <td><span class="award-reason">{{ $result->award_reason ?? '' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                @endforeach
            @endif
        @endforeach
    @endif

    <div class="footer">
        ChronoFront - ATS Sport | Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
