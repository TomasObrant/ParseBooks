<?php

namespace App\Controller\main;

use App\Entity\Chat;
use App\Entity\ChatMessage;
use App\Entity\User;
use App\Repository\ChatMessageRepository;
use App\Repository\ChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'chat_index')]
    public function index(ChatRepository $chatRepository)
    {
        $user = $this->getUser();
        $chats = $user->getChats();

        return $this->render('main/chat/index.html.twig', [
            'chats' => $chats,
            'user' => $user,
        ]);
    }

    #[Route('/chat/{id}', name: 'chat_show', methods: ['GET', 'POST'])]
    public function show(ManagerRegistry $doctrine, ChatMessageRepository $chatMessageRepository, Chat $chat, Request $request)
    {
        $messages = $chatMessageRepository->findBy(['chat' => $chat]);

        if ($request->getMethod() == 'POST') {
            $message = new ChatMessage();
            $message->setMessage($request->get('chat_message')['message']);
            $message->setCreatedAt(new \DateTimeImmutable());
            $message->setUser($this->getUser());
            $message->setChat($chat);
            $chat->setLatestMessage($message);
            $entityManager = $doctrine->getManager();
            $entityManager->persist($message);
            $entityManager->persist($chat);
            $entityManager->flush();

            return $this->redirectToRoute('chat_show', ['id' => $chat->getId()]);
        }


        return $this->render('main/chat/show.html.twig', [
            'chat' => $chat,
            'messages' => $messages,
        ]);
    }

    #[Route('/find-chat/{id}', name: 'chat_find', methods: ['GET'])]
    public function findChat(User $user, ChatRepository $chatRepository, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        $chat = $chatRepository->createIfNotExists($currentUser, $user);

        return $this->redirectToRoute('chat_show', ['id' => $chat->getId()]);
    }

}