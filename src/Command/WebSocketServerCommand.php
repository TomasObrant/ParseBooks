<?php

namespace App\Command;

use App\Entity\Chat;
use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'chat:serve')]
class WebSocketServerCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('chat:serve')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $server = new Server("0.0.0.0", 9502);

        $connections = [];

        $server->on("Start", function() use ($output) {
            $output->writeln("Swoole WebSocket Server started at http://127.0.0.1:9501\n");
        });

        $server->on('Open', function(Server $server, Request $request) use ($output, &$connections) {
            $chatId = $request->get['chat_id'];
            $userId = $request->get['user_id'];

            if ($this->isUserInChat($userId, $chatId)) {
                $connections[$request->fd] = $userId;
                $output->writeln("connection open: {$request->fd}, user_id: {$userId}");
            }
        });

        $server->on('Message', function(Server $server, Frame $frame) use ($output, &$connections){

            $output->writeln("received message: {$frame->data}");
            $data = json_decode($frame->data, true);

            $chatId = $data['chat_id'];
            $userId = $connections[$frame->fd] ?? null;

            if ($this->isUserInChat($userId, $chatId)) {
                $message = $data['message'];

                $newMessage = new ChatMessage();
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                $chat = $this->entityManager->getRepository(Chat::class)->find($chatId);

                $newMessage->setMessage($message);
                $newMessage->setCreatedAt(new \DateTimeImmutable());
                $newMessage->setUser($user);
                $newMessage->setChat($chat);
                $chat->setLatestMessage($newMessage);

                $this->entityManager->persist($newMessage);
                $this->entityManager->persist($chat);
                $this->entityManager->flush();

                foreach ($connections as $fd => $userId) {
                    if ($this->isUserInChat($userId, $chatId)) {
                        $server->push($fd, json_encode([$message, $userId, time()]));
                    }
                }
            }
        });

        $server->on('Close', function(Server $server, int $fd) use ($output, &$connections) {
            $output->writeln("connection close: {$fd}");
            unset($connections[$fd]);
        });

        $server->on('Disconnect', function(Server $server, int $fd) use ($output, &$connections) {
            $output->writeln("connection disconnect: {$fd}");
            foreach ($connections as $fd => $userId) {
                $server->disconnect($fd);
            }
        });

        $server->start();
    }

    private function isUserInChat(mixed $userId, mixed $chatId)
    {
        $chat = $this->entityManager->getRepository(Chat::class)->find($chatId);

        $chatUsers = $chat->getUser();
        foreach ($chatUsers as $chatUser) {
            $chatUserID = $chatUser->getId();
            if ($chatUserID == $userId) {
                return true;
            }
        }
    }

}
