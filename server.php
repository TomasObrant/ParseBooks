<?php

use App\Entity\Chat;
use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Symfony\Component\DependencyInjection\ServiceLocator;

$serviceLocator = new ServiceLocator([
    'entity_manager' => fn () => $this->get(EntityManagerInterface::class),
    // Добавьте здесь другие сервисы, если они нужны для вашего приложения.
]);

// Получаем EntityManager.
$entityManager = $serviceLocator->get('entity_manager');

$server = new Server("0.0.0.0", 9502);

$server->on("Start", function(Server $server)
{
    echo "OpenSwoole WebSocket Server is started at http://127.0.0.1:9502\n";
});

$server->on('Open', function(Server $server, Request $request)
{
    echo "connection open: {$request->fd}\n";
});



$server->on('Message', function(Server $server, Frame $frame) use ($entityManager){
    echo "received message: {$frame->data}\n";
    $data = json_decode($frame->data, true);

    if ($data['type'] === 'message') {

        $chatId = $data['chat_id'];
        $userId = $data['user_id'];
        $message = $data['message'];

//        $entityManager = $doctrine->getManager();
//        $entityManager = КАКОЙ СДЕСЬ КОД?
        $newMessage = new ChatMessage();
        $user = $entityManager->getRepository(User::class)->find($userId);
        $chat = $entityManager->getRepository(Chat::class)->find($chatId);

        $newMessage->setMessage($message);
        $newMessage->setCreatedAt(new \DateTimeImmutable());
        $newMessage->setUser($user);
        $newMessage->setChat($chat);
        $chat->setLatestMessage($message);

        $entityManager->persist($message);
        $entityManager->persist($chat);
        $entityManager->flush();

        echo "\n" . $data . "\n";

        $server->push($frame->fd, json_encode([$message, time()]));
    }
});

$server->on('Close', function(Server $server, int $fd)
{
    echo "connection close: {$fd}\n";
});

$server->on('Disconnect', function(Server $server, int $fd)
{
    echo "connection disconnect: {$fd}\n";
});

$server->start();