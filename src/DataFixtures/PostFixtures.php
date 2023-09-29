<?php

namespace App\DataFixtures;

use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PostFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;
    public function __construct(UserPasswordHasherInterface $hasher) {
        $this->hasher = $hasher;
    }
    public function load(ObjectManager $manager): void
    {
       $faker = Factory::create();

       for ($i = 1; $i <= 3; $i++) {
           $user = new User();
           $email = $faker->email;
           $user->setEmail($email);
           $user->setName($faker->name);
           $password = $this->hasher->hashPassword($user, $email);
           $user->setPassword($password);
           $manager->persist($user);

           for ($j = 1; $j <= 5; $j++) {
               $post = new Post();
               $post->setTitle($faker->sentence(10));
               $post->setContent($faker->text(3000));
               $number = $faker->numberBetween(-100, -2);
               $post->setCreatedAt(new \DateTimeImmutable($number.' days'));
               $post->setUser($user);
               $manager->persist($post);
           }
       }

        $manager->flush();
    }
}

