<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }
    
    public function by(Request $request): array
    {
        $status = $request->query->get('status');
        
        if ($status) {
            $messages = $this->getEntityManager()
                ->createQuery(
                    sprintf("SELECT m FROM App\Entity\Message m WHERE m.status = '%s'", $status)
                )
                ->getResult();
        } else {
            $messages = $this->findAll();
        }
        
        return $messages;
    }
}
