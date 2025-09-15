<?php

namespace App\DataFixtures;

use App\Entity\Restaurant;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;

class RestaurantFixtures extends Fixture
{
    public const USER_NB_TUPLES = 20;

    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= self::USER_NB_TUPLES; $i++){
        $restaurant = (new Restaurant())
        ->setName("Restaurant n°$i")
        ->setDescription("Description resto $i")
        ->setAmOpeningTime([])
        ->setPmOpeningTime([])
        ->setMaxGuest(random_int(10, 50));

        $manager->persist($restaurant);
        }
        $manager->flush();
    }
}
