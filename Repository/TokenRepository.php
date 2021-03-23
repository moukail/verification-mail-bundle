<?php

namespace Moukail\VerificationMailBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use Moukail\CommonToken\Entity\TokenInterface;
use Moukail\CommonToken\Repository\TokenRepositoryInterface;
use Moukail\CommonToken\Repository\TokenRepositoryTrait;
use Moukail\VerificationMailBundle\Entity\Token;

class TokenRepository extends ServiceEntityRepository implements TokenRepositoryInterface
{
    use TokenRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    /**
     * @param null $datetime
     *
     * @return Token[]
     */
    public function findInvalid($datetime = null)
    {
        $datetime = (null === $datetime) ? new \DateTime() : $datetime;

        return $this->createQueryBuilder('u')
            ->where('u.expiresAt < :datetime')
            ->setParameter(':datetime', $datetime)
            ->getQuery()
            ->getResult();
    }

    public function createTokenEntity(object $user, \DateTimeInterface $expiresAt, string $token): TokenInterface
    {
        return new Token($user, $expiresAt, $token);
    }
}
