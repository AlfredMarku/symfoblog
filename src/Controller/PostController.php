<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Pusher\Pusher;
use App\Services\TestCalculator;

#[Route('/', requirements: ['_locale' => 'en|pl'])]
class PostController extends AbstractController
{

    #[Route('/test', methods: ['GET'])]
    public function test(TestCalculator $calc)
    {
       return new Response('<h1>'.$calc->add(1,1).'</h1>');
    }
    
    #[Route('/{_locale}', methods: ['GET'], name: 'posts.index')]
    public function index(Request $request, ManagerRegistry $doctrine, string $_locale = 'en'): Response
    {
        $posts = $doctrine->getRepository(Post::class)->findAllPosts($request->query->getInt('page', 1));
        return $this->render('post/index.html.twig', [
            'posts' => $posts
        ]);
    }

    #[Route('/{_locale}/post/new', methods: ['GET', 'POST'], name: 'posts.new')]
    public function new(Request $request, EntityManagerInterface $entityManager, Pusher $pusher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $post = new Post();
        $post->setTitle('Write a blog post');
        $post->setContent('Post content');
        $post->setUser($this->getUser());
        $post->setCreatedAt(new \DateTimeImmutable('now'));
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            // $post = $form->getData();

            // ... perform some action, such as saving the task to the database
            $entityManager->persist($post);
            $entityManager->flush();
            $pusher->trigger('my-channel', 'new-post-event', 'New post: <a href="'.$this->generateUrl('posts.show',["id" => $post->getId()]).'">'.$post->getTitle().'</a>');

            return $this->redirectToRoute('posts.index');
        }
        return $this->render('post/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{_locale}/post/{id}', methods: ['GET'], name: 'posts.show')]
    public function show(Post $post, EntityManagerInterface $entityManager): Response
    {
        $isFollowing = $entityManager->getRepository(User::class)->isFollowing($this->getUser(), $post->getUser()) ?? false;
        $isLiked = $entityManager->getRepository(Post::class)->isLiked($this->getUser(), $post->getId()) ?? false;
        $isDisliked = $entityManager->getRepository(Post::class)->isDisliked($this->getUser(), $post->getId()) ?? false;
        return $this->render('post/show.html.twig', [
            'post' => $post,
            'isFollowing' => $isFollowing,
            'isLiked' => $isLiked,
            'isDisliked' => $isDisliked,
        ]);
    }

    #[Route('/{_locale}/post/{id}/edit', methods: ['GET', 'POST'], name: 'posts.edit')]
    public function edit(Post $post, Request $request, ManagerRegistry $doctrine): Response
    {
        // return $this->redirectToRoute('posts.index');
        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('POST_EDIT', $post);
        $post->setUpdatedAt(new \DateTimeImmutable('now'));
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $post = $form->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
             // ... perform some action, such as saving the task to the database
             return $this->redirectToRoute('posts.index');
        }
        return $this->render('post/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{_locale}/post/{id}/delete', methods: ['POST'], name: 'posts.delete')]
    public function delete(Post $post, ManagerRegistry $doctrine): Response
    {
        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('POST_DELETE', $post);
        $entityManager = $doctrine->getManager();
        $entityManager->remove($post);
        $entityManager->flush();
        return $this->redirectToRoute('posts.index');
    }

    #[Route('/{_locale}/posts/user/{id}', methods: ['GET'], name: 'posts.user')]
    public function user(Request $request, ManagerRegistry $doctrine,$id): Response
    {
        $posts = $doctrine->getRepository(Post::class)->findAllUserPosts($request->query->getInt('page', 1), $id);
        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'user' => $posts[0]?->getUser()->getName()
        ]);
    }

    #[Route('/{_locale}/toggleFollow/{user}', methods: ['GET'], name: 'toggleFollow')]
    public function toggleFollow(EntityManagerInterface $entityManager, User $user, Request $request): Response
    {
        // return new Response($user);
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $isFollowing = $entityManager->getRepository(User::class)->isFollowing($this->getUser(), $user) ?? false;
        if ($isFollowing) {
            $this->getUser()->removeFollowing($user);
        } else {
            $this->getUser()->addFollowing($user);
        }
        $entityManager->flush();
        $route = $request->headers->get('referer');
        return $this->redirect($route);
    }
}

