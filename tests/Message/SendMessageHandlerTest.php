<?php
namespace Message;

use App\Entity\Message;
use App\Message\SendMessage;
use App\Message\SendMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SendMessageHandlerTest extends KernelTestCase
{
    public function testSendMessageHandler(): void
    {
        // Mock EntityManagerInterface
        $entityManager = $this->createMock(EntityManagerInterface::class);

        // Configure the mock to expect exactly one call to persist
        $entityManager->expects($this->once())
        ->method('persist')
        ->willReturnCallback(function ($entity) {
            // Capture the entity for assertions
            $this->assertInstanceOf(Message::class, $entity, 'The entity should be an instance of Message.');
            $this->assertSame('Test message', $entity->getText(), 'The text of the message should match the SendMessage text.');
            $this->assertNotNull($entity->getUuid(), 'The message UUID should not be null.');
            $this->assertEquals('sent', $entity->getStatus(), 'The status of the message should be "sent".');
            $this->assertInstanceOf(\DateTime::class, $entity->getCreatedAt(), 'The createdAt property should be a DateTime instance.');
        });

        // Create an instance of SendMessageHandler with the mocked EntityManager
        $handler = new SendMessageHandler($entityManager);

        // Create a SendMessage object (assuming your SendMessage class is defined correctly)
        $sendMessage = new SendMessage('Test message');

        // Call the handler
        $handler($sendMessage); 
         
    }
    
}
