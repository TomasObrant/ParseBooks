<?php

namespace App\EventListener;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Book::class)]
class BookChangedNotifier
{

    const TELEGRAM_URL = 'https://api.telegram.org';

    public function postPersist(Book $book, postPersistEventArgs $event): void
    {
//        dd($book);
        $botToken = '6105444296:AAHe1kNDtwCYjKAEo8UQEm9iSGkYwYeTt2I';
        $chatId = '908714473';
        $text = 'hi...';

        $httpClient = HttpClient::create();
        $response = $httpClient->request('GET', self::TELEGRAM_URL . '/bot' . $botToken . '/sendMessage', [
            'query' => [
                'chat_id' => $chatId,
                'text' => $text
            ]
        ]);
    }
}