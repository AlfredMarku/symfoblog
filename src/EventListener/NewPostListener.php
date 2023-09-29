<?php
namespace App\EventListener;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NewPostListener {

    protected $mailer;

    public function __construct(MailerInterface $mailer)
    {
       $this->mailer = $mailer;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof Post) {
            return;
        }

        $entityManager = $args->getObjectManager();
        $users = $entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $email = (new Email())
            ->from('hello@example.com')
            ->to($user->getEmail())
            ->subject('New post from ' . $entity->getUser()->getName())
            ->html('<p>See new post! ' . $entity->getTitle(). '</p>');

            $this->mailer->send($email);
        }
    }
}
