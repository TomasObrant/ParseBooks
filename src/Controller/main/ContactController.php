<?php

namespace App\Controller\main;

use App\Entity\Message;
use App\Form\ContactFormType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact_index', methods: ['GET', 'POST'])]
    public function index(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $message_sent = false;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $message = new Message();
            $message->setName($data['name']);
            $message->setTheme($data['theme']);
            $message->setEmail($data['email']);
            $message->setMessage($data['message']);
            $message->setCreatedAt(new DateTimeImmutable());

            $entityManager->persist($message);
            $entityManager->flush();

            $email = (new Email())
                ->from($data['email'])
                ->to('dimkl2411@gmail.com')
                ->subject('Сообщение с сайта')
                ->text(sprintf(
                    'Имя: %s\nEmail: %s\Дата:\n%sСообщение:\n%s',
                    $data['name'],
                    $data['theme'],
                    $data['email'],
                    $data['message'],
                ));

            $mailer->send($email);
            $message_sent = true;
        }

        return $this->render('main/contact/index.html.twig', [
            'form' => $form->createView(),
            'message_sent' => $message_sent,
        ]);
    }
}
