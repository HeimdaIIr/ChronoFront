<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats - {{ $race->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 20px;
        }

        h1 {
            text-align: center;
            color: #1e3a8a;
            font-size: 18pt;
            margin-bottom: 5px;
        }

        h2 {
            text-align: center;
            color: #3B82F6;
            font-size: 14pt;
            margin-top: 0;
            margin-bottom: 20px;
        }

        h3 {
            color: #1e3a8a;
            font-size: 12pt;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 2px solid #3B82F6;
            padding-bottom: 5px;
        }

        .header-info {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #1e3a8a;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .position {
            font-weight: bold;
            color: #1e3a8a;
        }

        .bib-number {
            background-color: #3B82F6;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .category-badge {
            background-color: #10B981;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
        }

        .status-V {
            background-color: #10B981;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
        }

        .status-DNS {
            background-color: #F59E0B;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
        }

        .status-DNF {
            background-color: #EF4444;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
        }

        .status-DSQ {
            background-color: #DC2626;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
        }

        .status-NS {
            background-color: #6B7280;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #666;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <h1>{{ $race->event->name ?? 'Événement' }}</h1>
    <h2>{{ $race->name }}</h2>

    <div class="header-info">
        <p><strong>Date:</strong> {{ now()->format('d/m/Y H:i') }}</p>
        @if($race->distance)
            <p><strong>Distance:</strong> {{ $race->distance }} km</p>
        @endif
    </div>

    @if($displayMode === 'general')
        <!-- Classement Général -->
        <table>
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Dossard</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Sexe</th>
                    <th>Catégorie</th>
                    <th>Club</th>
                    <th>Temps</th>
                    <th>Vitesse</th>
                    <th>Pos. Cat.</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                    <tr>
                        <td class="position">{{ $result->position ?? '-' }}</td>
                        <td><span class="bib-number">{{ $result->entrant->bib_number ?? '' }}</span></td>
                        <td>{{ $result->entrant->lastname ?? '' }}</td>
                        <td>{{ $result->entrant->firstname ?? '' }}</td>
                        <td>{{ $result->entrant->gender ?? '' }}</td>
                        <td><span class="category-badge">{{ $result->entrant->category->name ?? 'N/A' }}</span></td>
                        <td>{{ $result->entrant->club ?? '-' }}</td>
                        <td><strong>{{ $result->formatted_time ?? 'N/A' }}</strong></td>
                        <td>{{ $result->speed ? number_format($result->speed, 2) . ' km/h' : 'N/A' }}</td>
                        <td>{{ $result->category_position ?? '-' }}</td>
                        <td><span class="status-{{ $result->status }}">{{ $result->status }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Classement par Catégorie -->
        @foreach($resultsByCategory as $categoryName => $categoryResults)
            <h3>{{ $categoryName }} ({{ $categoryResults->count() }} participants)</h3>

            <table>
                <thead>
                    <tr>
                        <th>Pos. Cat.</th>
                        <th>Pos. Général</th>
                        <th>Dossard</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Club</th>
                        <th>Temps</th>
                        <th>Vitesse</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryResults as $result)
                        <tr>
                            <td class="position">{{ $result->category_position ?? '-' }}</td>
                            <td>{{ $result->position ?? '-' }}</td>
                            <td><span class="bib-number">{{ $result->entrant->bib_number ?? '' }}</span></td>
                            <td>{{ $result->entrant->lastname ?? '' }}</td>
                            <td>{{ $result->entrant->firstname ?? '' }}</td>
                            <td>{{ $result->entrant->club ?? '-' }}</td>
                            <td><strong>{{ $result->formatted_time ?? 'N/A' }}</strong></td>
                            <td>{{ $result->speed ? number_format($result->speed, 2) . ' km/h' : 'N/A' }}</td>
                            <td><span class="status-{{ $result->status }}">{{ $result->status }}</span></td>
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
        <p>Document généré par ChronoFront - ATS Sport | {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
