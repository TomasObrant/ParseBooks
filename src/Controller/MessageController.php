<?php
namespace App\Controller;

use App\Event\TelegramMessageSentEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MessageController extends AbstractController
{
    #[Route('/message', name:'message')]
    public function index(EventDispatcherInterface $eventDispatcher): Response
    {
        $botToken = '6105444296:AAHe1kNDtwCYjKAEo8UQEm9iSGkYwYeTt2I';
        $chatId = '908714473';
        $text = 'hi..,';

        $telegramMessageSentEvent = new TelegramMessageSentEvent($botToken, $chatId, $text);
        $eventDispatcher->dispatch($telegramMessageSentEvent, TelegramMessageSentEvent::NAME);

        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
        ]);
    }
}