<?php
declare(strict_types=1);

namespace App\Controller;

use App\Message\SendMessage;
use App\Repository\MessageRepository;
use Controller\MessageControllerTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @see MessageControllerTest
 * TODO: review both methods and also the `openapi.yaml` specification
 *       Add Comments for your Code-Review, so that the developer can understand why changes are needed.
 */
class MessageController extends AbstractController
{
    /**
     * TODO: cover this method with tests, and refactor the code (including other files that need to be refactored)
     */
    #[Route('/messages')]
    public function list(Request $request, MessageRepository $messageRepository): Response
    {
        try {
            $status = $request->query->get('status');

            if(!empty($status) && is_string($status)){
                $messages = $messageRepository->findbyStatus($status);
            } else {
                $messages = $messageRepository->findAllMessages();
            }
    
            $responseData = array_map(fn($message) => [
                'uuid' => $message->getUuid(),
                'text' => $message->getText(),
                'status' => $message->getStatus(),
            ], $messages);

            return $this->json(['messages' => $responseData], 200, [], ['json_encode_options' => JSON_THROW_ON_ERROR]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500, [], ['json_encode_options' => JSON_THROW_ON_ERROR]);
        }
    }

    #[Route('/messages/send', methods: ['GET'])]
    public function send(Request $request, MessageBusInterface $bus): Response
    {
        $text = $request->query->get('text');
        
        if (empty($text) || !is_string($text)) {
            return $this->json(['error' => 'Text is required and must be a string'], 400);
        }

        $bus->dispatch(new SendMessage($text));
        
        return new Response('Successfully sent', 204);
    }
}