<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChronoFrontController extends Controller
{
    /**
     * Dashboard ChronoFront - Page d'accueil
     */
    public function dashboard()
    {
        return view('chronofront.dashboard');
    }

    /**
     * Gestion des événements
     */
    public function events()
    {
        return view('chronofront.events');
    }

    /**
     * Gestion des épreuves
     */
    public function races()
    {
        return view('chronofront.races');
    }

    /**
     * Gestion des participants
     */
    public function entrants()
    {
        return view('chronofront.entrants');
    }

    /**
     * Import CSV participants
     */
    public function entrantsImport()
    {
        return view('chronofront.entrants-import');
    }

    /**
     * Gestion des vagues
     */
    public function waves()
    {
        return view('chronofront.waves');
    }

    /**
     * Chronométrage en temps réel
     */
    public function timing()
    {
        return view('chronofront.timing');
    }

    /**
     * Résultats et classements
     */
    public function results()
    {
        return view('chronofront.results');
    }

    /**
     * Catégories FFA
     */
    public function categories()
    {
        return view('chronofront.categories');
    }
}
