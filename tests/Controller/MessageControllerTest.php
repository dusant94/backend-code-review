<?php
declare(strict_types=1);

namespace Controller;

use App\Message\SendMessage;
use App\Repository\MessageRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

class MessageControllerTest extends WebTestCase
{
    use InteractsWithMessenger;

    private function purgeData(EntityManagerInterface $entityManager): void
    {
        // Clear the database
        $purger = new ORMPurger($entityManager);
        $purger->purge();
    }

    private function loadTestData(EntityManagerInterface $entityManager): void
    {
        $this->purgeData($entityManager);
    
        for ($i = 1; $i <= 10; $i++) {
            $message = new \App\Entity\Message();
            $message->setUuid(Uuid::v6());
            $message->setText('Message text ' . $i);
            $message->setStatus('sent');
            $message->setCreatedAt(new \DateTime());
            $entityManager->persist($message);
        }

        for ($i = 1; $i <= 10; $i++) {
            $message = new \App\Entity\Message();
            $message->setUuid(Uuid::v6());
            $message->setText('Message text ' . $i);
            $message->setStatus('read');
            $message->setCreatedAt(new \DateTime());
            $entityManager->persist($message);
        }
        $entityManager->flush();
    }

    public function testListMessagesWithoutStatus(): void
    {

        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        $this->loadTestData($entityManager);

        $client->request('GET', '/messages');
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('messages', $data);
        $this->assertIsArray($data['messages']);
        $this->assertCount(20, $data['messages']); 

        $this->purgeData($entityManager);

    }

    public function testListMessagesWithStatus(): void
    {
        $status = 'sent';
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        $this->loadTestData($entityManager);

        $client->request('GET', '/messages', ['status' => $status]);
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('messages', $data);
        $this->assertIsArray($data['messages']);
        $this->assertCount(10, $data['messages']);  

        foreach ($data['messages'] as $message) {
            $this->assertEquals($status, $message['status']);
        }

        $this->purgeData($entityManager);
    }

    public function testListMessagesWithInvalidStatus(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        $this->loadTestData($entityManager);

        $client->request('GET', '/messages', ['status' => 'invalid-status']);
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('messages', $data);
        $this->assertIsArray($data['messages']);
        $this->assertEmpty($data['messages']);  // Assuming that invalid status returns an empty list

        $this->purgeData($entityManager);
    }

    public function testListMessagesHandlesExceptions(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $managerRegistry = $container->get('doctrine');

        // Create a mock of the MessageRepository with the constructor argument to reproduce 500 error
        $messageRepository = $this->getMockBuilder(MessageRepository::class)
            ->setConstructorArgs([$managerRegistry])
            ->onlyMethods(['findByStatus', 'findAllMessages'])
            ->getMock();
        $messageRepository->method('findByStatus')->willThrowException(new Exception('Database error'));
        $messageRepository->method('findAllMessages')->willThrowException(new Exception('Database error'));

        $client->getContainer()->set(MessageRepository::class, $messageRepository);
        $client->request('GET', '/messages?status=sent');
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Database error', $data['error']);

        $client->request('GET', '/messages');

    }
    
    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    private function getJsonResponseData(Response $response): array
    {
        $content = $response->getContent();
        if ($content === false) {
            throw new Exception('Response content is false');
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new Exception('Invalid JSON response');
        }
        
        return $data;
    }

    function test_that_it_sends_a_message(): void
    {
        $client = static::createClient();
        $client->request('GET', '/messages/send', [
            'text' => 'Hello World',
        ]);

        $this->assertResponseIsSuccessful();
        // This is using https://packagist.org/packages/zenstruck/messenger-test
        $this->transport('sync')
            ->queue()
            ->assertContains(SendMessage::class, 1);
    }
}