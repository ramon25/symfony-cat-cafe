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
            ],
            [
                'name' => 'Luna',
                'breed' => 'Siamese',
                'age' => 2,
                'color' => 'Cream',
                'description' => 'Elegant and chatty! Luna has beautiful blue eyes and loves to have conversations with visitors.',
            ],
            [
                'name' => 'Shadow',
                'breed' => 'Domestic Shorthair',
                'age' => 4,
                'color' => 'Black',
                'description' => 'A mysterious beauty who warms up to people over time. Shadow is incredibly loyal once you gain his trust.',
            ],
            [
                'name' => 'Mochi',
                'breed' => 'Scottish Fold',
                'age' => 1,
                'color' => 'Gray',
                'description' => 'Adorable and playful! Mochi loves chasing toys and will entertain you for hours.',
            ],
            [
                'name' => 'Ginger',
                'breed' => 'British Shorthair',
                'age' => 5,
                'color' => 'Orange',
                'description' => 'A dignified lady with a plush coat. Ginger enjoys lounging by the window and watching birds.',
            ],
            [
                'name' => 'Mittens',
                'breed' => 'Ragdoll',
                'age' => 2,
                'color' => 'White',
                'description' => 'True to the breed, Mittens goes completely limp when you pick her up. She is pure fluff and love.',
            ],
            [
                'name' => 'Felix',
                'breed' => 'Tuxedo',
                'age' => 3,
                'color' => 'Tuxedo',
                'description' => 'Always dressed to impress! Felix is a sophisticated gentleman who greets every visitor.',
            ],
            [
                'name' => 'Cleo',
                'breed' => 'Abyssinian',
                'age' => 2,
                'color' => 'Brown',
                'description' => 'Athletic and curious, Cleo loves to explore every corner of the cafe. She is always up for an adventure.',
            ],
        ];

        foreach ($cats as $catData) {
            $cat = new Cat();
            $cat->setName($catData['name']);
            $cat->setBreed($catData['breed']);
            $cat->setAge($catData['age']);
            $cat->setColor($catData['color']);
            $cat->setDescription($catData['description']);

            // Randomize stats a bit
            $cat->setHunger(rand(30, 70));
            $cat->setHappiness(rand(40, 80));
            $cat->setEnergy(rand(30, 70));

            $manager->persist($cat);
        }

        $manager->flush();
    }
}
