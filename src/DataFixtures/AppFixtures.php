<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\PostFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $posts = PostFactory::new()->published()->many(5)->create();

        $manager->flush();
    }
}
