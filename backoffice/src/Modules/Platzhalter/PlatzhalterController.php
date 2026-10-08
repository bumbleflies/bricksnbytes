<?php
declare(strict_types=1);

namespace Backoffice\Modules\Platzhalter;

use Backoffice\Router;
use Backoffice\View;

// Modules that are planned but not built yet: page with heading and "Kommt bald"
final class PlatzhalterController
{
    private const PAGES = [
        '/auftraege' => ['Aufträge', 'Anfragen und Angebote für Schulen, Horte, Kitas, Geburtstage und Firmen – vom ersten Kontakt bis zur Abrechnung.'],
        '/kurse' => ['Kurse', 'Kursreihen mit Kursart, Format, Alter und Plätzen.'],
        '/termine' => ['Termine', 'Einzeltermine mit Ort, Kursleiter, Teilnehmerliste, Anwesenheit, Warteliste und Material.'],
        '/buchungen' => ['Buchungen', 'Welches Kind ist in welchem Kurs – später auch aus dem Shop übernommen.'],
        '/rechnungen' => ['Rechnungen', 'Rechnungen aus Aufträgen und Buchungen, PDF, fortlaufende Nummern, Zahlungen und Mahnungen.'],
        '/forecast' => ['Forecast', 'Umsatz pro Monat: fix (bezahlt und bestätigt) plus gewichtete offene Angebote.'],
        '/kursleiter' => ['Kursleiter', 'Kursleiterinnen und Kursleiter, später mit eigenem, eingeschränktem Zugang.'],
        '/einstellungen' => ['Einstellungen', 'Benutzer, Kursarten, Preise und Wahrscheinlichkeiten für den Forecast.'],
    ];

    public static function register(Router $router): void
    {
        foreach (self::PAGES as $path => [$title, $text]) {
            $router->get($path, static fn () => View::render('platzhalter', ['title' => $title, 'text' => $text]));
        }
    }
}
