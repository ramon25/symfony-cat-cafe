<?php

namespace App\Message;

/**
 * Message dispatched by the scheduler to trigger cat movements.
 * Randomly makes some cats arrive at or leave the cafe.
 */
final class CatMovementMessage
{
    public function __construct(
        /** Probability (0-100) that an available cat will leave the cafe */
        public readonly int $leaveChance = 20,
        /** Probability (0-100) that an absent cat will return to the cafe */
        public readonly int $returnChance = 30,
    ) {
    }
}
