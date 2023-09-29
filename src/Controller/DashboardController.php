<?php

namespace App\Controller;
use App\Entity\Image;
use App\Form\DeleteAccountFormType;
use App\Form\ChangePasswordFormType;
use App\Form\ImageFormType;
use App\Form\UserFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Services\ImageUploader;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/dashboard/profile', name: 'app_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager, Security $security, ImageUploader $imageUploader): Response
    {
        // change image
        $image = new Image();
        $imageForm = $this->createForm(ImageFormType::class, $image);
        $imageForm->handleRequest($request);
        $user = $this->getUser();
        if ($imageForm->isSubmitted() && $imageForm->isValid()) {
            $imageFile = $imageForm->get('imageFile')->getData();
            if ($imageFile) {
                if ($user->getImage()?->getPath()) {
                    unlink($this->getParameter('images_directory') . '/' .  $user->getImage()->getPath());
                }

                $newFilename = $imageUploader->upload($imageFile);

                $image->setPath($newFilename);
                if ($user->getImage()) {
                     $oldImage = $entityManager->getRepository(Image::class)->find($user->getImage()->getId());
                     $entityManager->remove($oldImage);
                }
            $user->setImage($image);
            $entityManager->persist($image);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash(
                'status-image',
                'image-updated'
            );
            }
            return $this->redirectToRoute('app_profile');
        }

        // change user email, name
        // $user = $this->getUser();
        $userForm = $this->createForm(UserFormType::class, $user);
        $userForm->handleRequest($request);

        if ($userForm->isSubmitted() && $userForm->isValid()) {
            // $user = $userForm->getData();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash(
                'status-profile-information',
                'user-updated'
            );
            return $this->redirectToRoute('app_profile');
        }

        // change password
        $passwordForm = $this->createForm(ChangePasswordFormType::class, $user);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            // $user = $passwordForm->getData();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash(
                'status-password',
                'password-changed'
            );
            return $this->redirectToRoute('app_profile');
        }

        // delete account
        $deleteAccountForm = $this->createForm(DeleteAccountFormType::class, $user);
        $deleteAccountForm->handleRequest($request);
        
        if ($deleteAccountForm->isSubmitted() && $deleteAccountForm->isValid()) {
            // $user = $deleteAccountForm->getData();
            $security->logout(false);
            $entityManager->remove($user);
            $entityManager->flush();
            $request->getSession()->invalidate();
            return $this->redirectToRoute('posts.index');
        }

        return $this->render('dashboard/edit.html.twig', [
            'imageForm' => $imageForm,
            'userForm' => $userForm,
            'passwordForm' => $passwordForm,
            'deleteAccountForm' => $deleteAccountForm,
        ]);
    }
}

