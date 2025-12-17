<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Résultats {{ $race->name }}</title>
    <style>
        @page {
            margin: 1.5cm 1cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #000;
            line-height: 1.2;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18pt;
            color: #1e3a8a;
            margin: 0 0 3px 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 14pt;
            color: #3B82F6;
            margin: 0 0 5px 0;
            font-weight: normal;
        }

        .header-info {
            font-size: 8pt;
            color: #666;
            margin: 3px 0;
        }

        .category-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 20px 0 8px 0;
            padding: 4px 8px;
            background-color: #f0f4ff;
            border-left: 3px solid #3B82F6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #1e3a8a;
            color: white;
            padding: 5px 3px;
            text-align: left;
            font-size: 7pt;
            font-weight: bold;
            border: 1px solid #1e3a8a;
        }

        td {
            padding: 3px 3px;
            border: 1px solid #ddd;
            font-size: 7pt;
            line-height: 1.1;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .pos {
            font-weight: bold;
            color: #1e3a8a;
            text-align: center;
            width: 30px;
        }

        .bib {
            font-weight: bold;
            text-align: center;
            width: 35px;
        }

        .name {
            font-weight: 600;
        }

        .time {
            font-weight: bold;
            text-align: center;
            width: 55px;
        }

        .speed {
            text-align: center;
            width: 50px;
        }

        .category {
            text-align: center;
            width: 60px;
            font-size: 7pt;
        }

        .status {
            text-align: center;
            width: 35px;
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
            padding-top: 5px;
            border-top: 1px solid #ddd;
        }

        .page-break {
            page-break-after: always;
        }

        .total-participants {
            text-align: right;
            font-size: 7pt;
            color: #666;
            margin-bottom: 3px;
            font-style: italic;
        }
    </style>
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

        <table>
            <thead>
                <tr>
                    <th class="pos">Pos.</th>
                    <th class="bib">Dos.</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th style="width: 25px; text-align: center;">Sexe</th>
                    <th class="category">Catégorie</th>
                    <th>Club</th>
                    <th class="time">Temps</th>
                    <th class="speed">Vitesse</th>
                    <th class="pos">Pos. Cat.</th>
                    <th class="status">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                    <tr>
                        <td class="pos">{{ $result->position ?? '-' }}</td>
                        <td class="bib">{{ $result->entrant->bib_number ?? '' }}</td>
                        <td class="name">{{ strtoupper($result->entrant->lastname ?? '') }}</td>
                        <td>{{ $result->entrant->firstname ?? '' }}</td>
                        <td style="text-align: center;">{{ $result->entrant->gender ?? '' }}</td>
                        <td class="category">{{ $result->entrant->category->name ?? 'N/A' }}</td>
                        <td>{{ $result->entrant->club ?? '-' }}</td>
                        <td class="time">{{ $result->formatted_time ?? 'N/A' }}</td>
                        <td class="speed">{{ $result->speed ? number_format($result->speed, 2) : '-' }}</td>
                        <td class="pos">{{ $result->category_position ?? '-' }}</td>
                        <td class="status status-{{ strtolower($result->status) }}">{{ $result->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Classement par Catégorie -->
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
