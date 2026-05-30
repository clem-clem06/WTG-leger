<?php

namespace App\Enum;

/**
 * Statuts possibles d'un paiement (colonne `status` de Payment).
 *
 * Note : le client lourd Java écrit ses propres valeurs en anglais
 * ('paid', 'cancel'). Le template d'affichage gère les deux vocabulaires.
 */
final class PaymentStatus
{
    public const PENDING = 'pending';      // virement en attente de réception
    public const COMPLETED = 'completed';  // paiement carte réussi
    public const CANCELLED = 'annulé';     // annulé (délai dépassé)

    private function __construct()
    {
    }
}
