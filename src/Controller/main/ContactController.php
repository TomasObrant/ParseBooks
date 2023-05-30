<?php

namespace App\Controller\main;

use App\Entity\Message;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;


class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact_index', methods: ['GET', 'POST'])]
    public function index(UserInterface $user, Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        phpinfo();

        $message_sent = false;

        if ($request->getMethod() == 'POST') {

            $data = $request->request->all();
            $date = new DateTimeImmutable();

            $message = new Message();
            $message->setTheme($data['theme']);
            $message->setMessage($data['message']);
            $message->setCreatedAt($date);
            $message->setUser($user);

            $entityManager->persist($message);
            $entityManager->flush();
            $email = (new Email())
                ->from($user->getEmail())
                ->to('dimkl2411@gmail.com')
                ->subject('Сообщение с сайта')
                ->text(sprintf(
                    'Тема: %s\nДата:\n%sСообщение:\n%s',
                    $data['theme'],
                    $date->format('Y-m-d H:i:s'),
                    $data['message'],
                ));

            $mailer->send($email);
            $message_sent = true;
        }

        return $this->render('main/contact/index.html.twig', [
            'message_sent' => $message_sent,
        ]);
    }
}
