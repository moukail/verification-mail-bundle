<?php

namespace Moukail\VerificationMailBundle\Controller;

use Moukail\CommonToken\Controller\ControllerTrait;
use Moukail\CommonToken\Entity\TokenInterface;
use Moukail\CommonToken\Exception\ExceptionInterface;
use Moukail\CommonToken\HelperInterface;

use Moukail\CommonToken\Repository\UserRepositoryInterface;
use Moukail\VerificationMailBundle\Message\EmailVerificationMail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VerificationMailController extends AbstractController
{
    use ControllerTrait;

    private $helper;
    private $userRepository;
    private $validator;
    private $bus;
    /** @var ParameterBagInterface */
    private $params;

    public function __construct(HelperInterface $helper, UserRepositoryInterface $userRepository, ValidatorInterface $validator, MessageBusInterface $bus, ParameterBagInterface $params)
    {
        $this->helper = $helper;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->bus = $bus;
        $this->params = $params;
    }

    public function request(Request $request): JsonResponse
    {
        $email = $this->emailValidation($request, $this->validator);

        try {
            /** @var TokenInterface $tokenEntity */
            $tokenEntity = $this->generateTokenEntity($email);
        } catch (ExceptionInterface $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getReason()
            ], Response::HTTP_OK);
        }

        $this->bus->dispatch(new EmailVerificationMail($tokenEntity->getUser()->getEmail(), [
            'name' => $tokenEntity->getUser()->getLastName(),
            'frontend_url' => $this->params->get('moukail_verification_mail.email_base_url'),
            'token' => $tokenEntity->getToken(),
        ]));

        return $this->json([
            'status' => 'success',
            'message' => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * @param string $token
     * @return JsonResponse
     */
    public function verify(string $token): JsonResponse
    {
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->helper->validateTokenAndFetchUser($token);
        } catch (ExceptionInterface $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getReason()
            ], Response::HTTP_OK);
        }

        $user->setIsVerified(true);

        $this->helper->removeTokenEntity($token);

        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'success'
        ], Response::HTTP_OK);
    }
}
