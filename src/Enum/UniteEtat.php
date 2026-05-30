<?php

namespace App\Enum;

/**
 * États possibles d'une unité (colonne `etat`).
 *
 * Centralise les valeurs en un seul endroit pour éviter les fautes de casse
 * ou de frappe (ex. le bug "en attente de paiement" vs "En attente de paiement").
 */
final class UniteEtat
{
    public const OK = 'OK';
    public const EN_ATTENTE_PAIEMENT = 'en attente de paiement';

    private function __construct()
    {
        // Classe de constantes : pas d'instanciation.
    }
}
