<?php

namespace App\Message;

/**
 * Message dispatched by the scheduler to update cat stats over time.
 * Simulates cats getting hungry and tired naturally.
 */
final class UpdateCatStatsMessage
{
    public function __construct(
        public readonly int $hungerIncrease = 5,
        public readonly int $energyDecrease = 3,
    ) {
    }
}
