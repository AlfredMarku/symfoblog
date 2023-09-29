<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;

#[AsLiveComponent('search')]
final class SearchComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getPosts(): array
    {
        return $this->query ? $this->entityManager->getRepository(Post::class)->searchPosts($this->query) : [];
    }
}

