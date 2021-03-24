<?php

namespace Moukail\VerificationMailBundle;

use Moukail\CommonToken\Cleaner;
use Moukail\CommonToken\Entity\TokenInterface;
use Moukail\CommonToken\Exception\ExpiredTokenException;
use Moukail\CommonToken\Exception\InvalidTokenException;
use Moukail\CommonToken\Exception\TooManyRequestsException;
use Moukail\CommonToken\Generator\TokenGenerator;
use Moukail\CommonToken\HelperInterface;
use Moukail\VerificationMailBundle\Repository\TokenRepository;
use Ramsey\Uuid\Uuid;

final class Helper implements HelperInterface
{
    /**
     * The first 20 characters of the token are a "selector".
     */
    private const SELECTOR_LENGTH = 20;

    private $tokenGenerator;
    private $cleaner;
    private $repository;

    /**
     * @var int How long a token is valid in seconds
     */
    private $requestLifetime;

    /**
     * @var int Another password reset cannot be made faster than this throttle time in seconds
     */
    private $requestThrottleTime;

    public function __construct(TokenGenerator $generator, Cleaner $cleaner, TokenRepository $repository, int $requestLifetime, int $requestThrottleTime)
    {
        $this->tokenGenerator = $generator;
        $this->cleaner = $cleaner;
        $this->repository = $repository;
        $this->requestLifetime = $requestLifetime;
        $this->requestThrottleTime = $requestThrottleTime;
    }

    /**
     * {@inheritdoc}
     *
     * Some of the cryptographic strategies were taken from
     * https://paragonie.com/blog/2017/02/split-tokens-token-based-authentication-protocols-without-side-channels
     *
     * @throws TooManyRequestsException
     */
    public function generateTokenEntity(object $user): TokenInterface
    {
        $this->cleaner->handleGarbageCollection();

        if ($availableAt = $this->hasUserHitThrottling($user)) {
            throw new TooManyRequestsException($availableAt);
        }

        $expiresAt = new \DateTimeImmutable(\sprintf('+%d seconds', $this->requestLifetime));

        //$generatedAt = ($expiresAt->getTimestamp() - $this->emailVerificationRequestLifetime);

        //$tokenComponents = $this->tokenGenerator->createToken($expiresAt, $this->repository->getUserIdentifier($user));

        $passwordResetRequest = $this->repository->createTokenEntity(
            $user,
            $expiresAt,
            Uuid::uuid4()->toString()
        );

        $this->repository->persistTokenEntity($passwordResetRequest);

        // final "public" token is the selector + non-hashed verifier token
        return $passwordResetRequest;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ExpiredTokenException
     * @throws InvalidTokenException
     */
    public function validateTokenAndFetchUser(string $fullToken): object
    {
        $this->cleaner->handleGarbageCollection();

        if (36 !== \strlen($fullToken)) {
            throw new InvalidTokenException();
        }

        $resetRequest = $this->findTokenEntity($fullToken);

        if (null === $resetRequest) {
            throw new InvalidTokenException();
        }

        if ($resetRequest->isExpired()) {
            throw new ExpiredTokenException();
        }

        $user = $resetRequest->getUser();

/*        $hashedVerifierToken = $this->tokenGenerator->createToken(
            $resetRequest->getExpiresAt(),
            $this->repository->getUserIdentifier($user),
            \substr($fullToken, self::SELECTOR_LENGTH)
        );

        if (false === \hash_equals($resetRequest->getHashedToken(), $hashedVerifierToken->getHashedToken())) {
            throw new InvalidEmailVerificationTokenException();
        }*/

        return $user;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidTokenException
     */
    public function removeTokenEntity(string $fullToken): void
    {
        $request = $this->findTokenEntity($fullToken);

        if (null === $request) {
            throw new InvalidTokenException();
        }

        $this->repository->removeTokenEntity($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenLifetime(): int
    {
        return $this->requestLifetime;
    }

    private function findTokenEntity(string $token): ?TokenInterface
    {
        //$selector = \substr($token, 0, self::SELECTOR_LENGTH);

        return $this->repository->findTokenEntity($token);
    }

    private function hasUserHitThrottling(object $user): ?\DateTimeInterface
    {
        /** @var \DateTime|\DateTimeImmutable|null $lastRequestDate */
        $lastRequestDate = $this->repository->getMostRecentNonExpiredRequestDate($user);

        if (null === $lastRequestDate) {
            return null;
        }

        $availableAt = (clone $lastRequestDate)->add(new \DateInterval("PT{$this->requestThrottleTime}S"));

        if ($availableAt > new \DateTime('now')) {
            return $availableAt;
        }

        return null;
    }
}
