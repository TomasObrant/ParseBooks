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

        $server->on("Start", function(Server $server) use ($output) {
            $output->writeln("OpenSwoole WebSocket Server is started at http://127.0.0.1:9502");
        });

        $server->on('Open', function(Server $server, Request $request) use ($output) {
            $output->writeln("connection open: {$request->fd}");
        });

        $server->on('Message', function(Server $server, Frame $frame) use ($output){

            $output->writeln("received message: {$frame->data}");
            $data = json_decode($frame->data, true);

            if ($data['type'] === 'message') {

                $chatId = $data['chat_id'];
                $userId = $data['user_id'];
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

                foreach ($server->connections as $fd) {
                    $server->push($fd, json_encode([$message, $userId, time()]));
                }
            }
        });

        $server->on('Close', function(Server $server, int $fd) use ($output) {
            $output->writeln("connection close: {$fd}");
        });

        $server->on('Disconnect', function(Server $server, int $fd) use ($output) {
            $output->writeln("connection disconnect: {$fd}");
        });

        $server->start();
    }

}
