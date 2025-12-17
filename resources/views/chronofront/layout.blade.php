<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ChronoFront') - ATS Sport</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --primary-color: #3B82F6;
            --secondary-color: #10B981;
            --danger-color: #EF4444;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }

        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            width: 24px;
            margin-right: 10px;
        }

        .main-content {
            padding: 30px;
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 12px;
        }

        .card-header {
            background-color: white;
            border-bottom: 2px solid #f1f5f9;
            padding: 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background-color: var(--secondary-color);
            border: none;
        }

        .btn-danger {
            background-color: var(--danger-color);
            border: none;
        }

        .badge-status-v { background-color: #10B981; }
        .badge-status-dns { background-color: #F59E0B; }
        .badge-status-dnf { background-color: #EF4444; }
        .badge-status-dsq { background-color: #DC2626; }
        .badge-status-ns { background-color: #6B7280; }

        .table-hover tbody tr:hover {
            background-color: #f8fafc;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2563eb 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }
    </style>

    @yield('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 col-lg-2 d-md-block sidebar px-0">
                <div class="position-sticky pt-4">
                    <div class="text-center mb-4">
                        <h4 class="text-white fw-bold"><i class="bi bi-stopwatch"></i> ChronoFront</h4>
                        <p class="text-white-50 small mb-0">Chronométrage Sportif</p>
                    </div>

                    <ul class="nav flex-column px-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chronofront.dashboard') ? 'active' : '' }}"
                               href="{{ route('dashboard') }}">
                                <i class="bi bi-house-door"></i> Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chronofront.events') ? 'active' : '' }}"
                               href="{{ route('events') }}">
                                <i class="bi bi-calendar-event"></i> Événements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chronofront.entrants*') ? 'active' : '' }}"
                               href="{{ route('entrants') }}">
                                <i class="bi bi-people"></i> Participants
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chronofront.races') ? 'active' : '' }}"
                               href="{{ route('races') }}">
                                <i class="bi bi-trophy"></i> Épreuves
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chronofront.waves') ? 'active' : '' }}"
                               href="{{ route('waves') }}">
                                <i class="bi bi-flag"></i> Vagues
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chronofront.categories') ? 'active' : '' }}"
                               href="{{ route('categories') }}">
                                <i class="bi bi-grid"></i> Catégories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chronofront.timing') ? 'active' : '' }}"
                               href="{{ route('timing') }}">
                                <i class="bi bi-stopwatch"></i> Chronométrage
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chronofront.results') ? 'active' : '' }}"
                               href="{{ route('results') }}">
                                <i class="bi bi-bar-chart"></i> Résultats
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto col-lg-10 main-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Axios for API calls -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        // Configure Axios defaults
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.baseURL = '/api';
    </script>

    @yield('scripts')
</body>
</html>
