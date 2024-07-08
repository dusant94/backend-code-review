<?php
declare(strict_types=1);

namespace Repository;

use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MessageRepositoryTest extends KernelTestCase
{
    public function test_it_has_connection(): void
    {
        self::bootKernel();
        
        $messageRepository = self::getContainer()->get(MessageRepository::class);
        assert($messageRepository instanceof MessageRepository);
        $this->assertSame([], $messageRepository->findAllMessages());
    }
}