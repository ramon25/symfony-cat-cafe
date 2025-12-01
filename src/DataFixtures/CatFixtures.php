<?php

namespace App\DataFixtures;

use App\Entity\Cat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CatFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cats = [
            [
                'name' => 'Whiskers',
                'breed' => 'Maine Coon',
                'age' => 3,
                'color' => 'Orange',
                'description' => 'A gentle giant with a fluffy tail. Whiskers loves to cuddle and will follow you around the cafe.',
                'preferredInteraction' => Cat::INTERACTION_PET,
            ],
            [
                'name' => 'Luna',
                'breed' => 'Siamese',
                'age' => 2,
                'color' => 'Cream',
                'description' => 'Elegant and chatty! Luna has beautiful blue eyes and loves to have conversations with visitors.',
                'preferredInteraction' => Cat::INTERACTION_PLAY,
            ],
            [
                'name' => 'Shadow',
                'breed' => 'Domestic Shorthair',
                'age' => 4,
                'color' => 'Black',
                'description' => 'A mysterious beauty who warms up to people over time. Shadow is incredibly loyal once you gain his trust.',
                'preferredInteraction' => Cat::INTERACTION_REST,
            ],
            [
                'name' => 'Mochi',
                'breed' => 'Scottish Fold',
                'age' => 1,
                'color' => 'Gray',
                'description' => 'Adorable and playful! Mochi loves chasing toys and will entertain you for hours.',
                'preferredInteraction' => Cat::INTERACTION_PLAY,
            ],
            [
                'name' => 'Ginger',
                'breed' => 'British Shorthair',
                'age' => 5,
                'color' => 'Orange',
                'description' => 'A dignified lady with a plush coat. Ginger enjoys lounging by the window and watching birds.',
                'preferredInteraction' => Cat::INTERACTION_REST,
            ],
            [
                'name' => 'Mittens',
                'breed' => 'Ragdoll',
                'age' => 2,
                'color' => 'White',
                'description' => 'True to the breed, Mittens goes completely limp when you pick her up. She is pure fluff and love.',
                'preferredInteraction' => Cat::INTERACTION_PET,
            ],
            [
                'name' => 'Felix',
                'breed' => 'Tuxedo',
                'age' => 3,
                'color' => 'Tuxedo',
                'description' => 'Always dressed to impress! Felix is a sophisticated gentleman who greets every visitor.',
                'preferredInteraction' => Cat::INTERACTION_FEED,
            ],
            [
                'name' => 'Cleo',
                'breed' => 'Abyssinian',
                'age' => 2,
                'color' => 'Brown',
                'description' => 'Athletic and curious, Cleo loves to explore every corner of the cafe. She is always up for an adventure.',
                'preferredInteraction' => Cat::INTERACTION_PLAY,
            ],
        ];

        foreach ($cats as $catData) {
            $cat = new Cat();
            $cat->setName($catData['name']);
            $cat->setBreed($catData['breed']);
            $cat->setAge($catData['age']);
            $cat->setColor($catData['color']);
            $cat->setDescription($catData['description']);
            $cat->setPreferredInteraction($catData['preferredInteraction']);

            // Randomize stats a bit
            $cat->setHunger(rand(30, 70));
            $cat->setHappiness(rand(40, 80));
            $cat->setEnergy(rand(30, 70));

            $manager->persist($cat);
        }

        $manager->flush();
    }
}
