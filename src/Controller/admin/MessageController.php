<?php

namespace App\Controller\admin;

use App\Command\ParseBooksCommand;
use App\Entity\Message;
use App\Form\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class MessageController extends AbstractController
{
    #[Route('admin/message', name: 'admin_message_index', methods: ['GET'])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $messages =  $doctrine
            ->getRepository(Message::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/message/index.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('admin/message/{id}', name: 'admin_message_show', methods: ['GET', 'POST'])]
    public function show(ManagerRegistry $doctrine, Request $request, Message $message, MailerInterface $mailer): Response
    {
        $message->setIsRead(true);
        $message_sent = false;

        if ($request->getMethod() == 'POST') {
            $email = (new Email())
                ->from('dimkl2411@gmail.com')
                ->to($message->getEmail())
                ->subject("Ответ на сообщение")
                ->text(sprintf(
                    'Сообщение:\n%sEmail: %s\n',
                    $request->request->get('message'),
                    'dimkl2411@gmail.com'
                ));
            $message->setAnswer($request->request->get('message'));

            $mailer->send($email);
            $message_sent = true;
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($message);
        $entityManager->flush();

        return $this->render('admin/message/show.html.twig', [
            'message' => $message,
            'message_sent' => $message_sent,
        ]);
    }

    #[Route('admin/messages/delete', name: 'admin_message_delete', methods: ['POST'])]
    public function deleteSelected(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ids = $request->request->all('ids');

        if (!empty($ids)) {
            foreach ($ids as $id) {
                $message = $entityManager->getRepository(Message::class)->findOneBy(['id' => $id]);
                $entityManager->remove($message);
                $entityManager->flush();
            }
        }
        return $this->redirectToRoute('admin_message_index');
    }
}