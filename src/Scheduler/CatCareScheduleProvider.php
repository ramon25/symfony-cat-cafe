<?php

namespace App\Scheduler;

use App\Message\CatMovementMessage;
use App\Message\UpdateCatStatsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Scheduler for automated cat care tasks.
 *
 * Dispatches messages at regular intervals to simulate
 * cats naturally getting hungry and tired over time,
 * and cats coming and going from the cafe.
 */
#[AsSchedule('cat_care')]
final class CatCareScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                // Update cat stats every 5 minutes
                // Cats get a bit hungrier and more tired naturally
                RecurringMessage::every('5 minutes', new UpdateCatStatsMessage(
                    hungerIncrease: 5,
                    energyDecrease: 3,
                ))
            )
            ->add(
                // Every hour, cats get significantly hungrier (meal time!)
                RecurringMessage::every('1 hour', new UpdateCatStatsMessage(
                    hungerIncrease: 15,
                    energyDecrease: 5,
                ))
            )
            ->add(
                // Every 10 minutes, some cats may arrive or leave the cafe
                // 20% chance for a cat to leave, 30% chance for a cat to return
                RecurringMessage::every('10 minutes', new CatMovementMessage(
                    leaveChance: 20,
                    returnChance: 30,
                ))
            )
            ->add(
                // Every 2 hours, higher chance of cat movements (shift change!)
                // More cats tend to come and go during these times
                RecurringMessage::every('2 hours', new CatMovementMessage(
                    leaveChance: 40,
                    returnChance: 50,
                ))
            );
    }
}
