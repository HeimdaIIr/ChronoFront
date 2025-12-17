<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Récompenses {{ $race->name }}</title>
    <style>
        @page {
            margin: 1.5cm 1cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.3;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 4px solid #f59e0b;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 22pt;
            color: #1e3a8a;
            margin: 0 0 5px 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 18pt;
            color: #f59e0b;
            margin: 0 0 8px 0;
            font-weight: bold;
        }

        .header .trophy {
            font-size: 40pt;
            color: #f59e0b;
            margin-bottom: 10px;
        }

        .header-info {
            font-size: 9pt;
            color: #666;
            margin: 5px 0;
        }

        .config-info {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 9pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f59e0b;
            color: white !important;
            padding: 8px 4px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
            border: 1px solid #f59e0b;
        }

        td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            font-size: 9pt;
            line-height: 1.2;
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
            background-color: #fef3c7;
            color: #92400e;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
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
            font-size: 11pt;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 15px;
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
        <div class="trophy">🏆</div>
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
        🎖️ {{ $results->count() }} RÉCOMPENSES AU TOTAL 🎖️
    </div>

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
                    <td><span class="award-reason">{{ $result->award_reason ?? 'Récompensé' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        ChronoFront - ATS Sport | Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
