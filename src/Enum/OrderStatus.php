<?php

namespace App\Enum;

/**
 * Statuts possibles d'une commande (colonne `status` de Order).
 *
 * Note : le client lourd Java écrit ses propres valeurs en anglais
 * ('paid', 'cancel'). Le template d'affichage gère les deux vocabulaires.
 */
final class OrderStatus
{
    public const PENDING = 'pending';    // virement en attente
    public const PAID = 'payée';         // payée (carte ou virement validé)
    public const CANCELLED = 'annulée';  // annulée (délai dépassé)

    private function __construct()
    {
    }
}
