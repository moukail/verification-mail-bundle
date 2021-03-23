<?php

namespace Moukail\VerificationMailBundle\MessageHandler;

use Moukail\VerificationMailBundle\Message\VerificationMail;
use Psr\Log\LoggerInterface;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Address;

class VerificationMailHandler implements MessageHandlerInterface
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $fromAddress;
    private string $fromName;

    /**
     * PartnerActivationMailHandler constructor.
     *
     * @param MailerInterface $mailer
     * @param LoggerInterface $logger
     * @param string $fromAddress
     * @param string $fromName
     */
    public function __construct(MailerInterface $mailer, LoggerInterface $logger, string $fromAddress, string $fromName)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    public function __invoke(VerificationMail $message)
    {
        $message = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to(new Address($message->getEmail()))
            ->subject('Email verification') // todo add translation

            // path of the Twig template to render
            ->htmlTemplate('@MoukailVerificationMail/emails/verification_mail.html.twig')
            ->textTemplate('@MoukailVerificationMail/emails/verification_mail.text.twig')
            // pass variables (name => value) to the template
            ->context($message->getContext())
        ;

        // todo get headers from yaml config file
        $headers = $message->getHeaders();
        $headers->addTextHeader('X-Mailgun-Tag', 'VerificationMail');
        $headers->addTextHeader('X-Mailgun-Track-Clicks', 'yes');
        $headers->addTextHeader('X-Mailgun-Track-Opens', 'yes');
        $headers->addTextHeader('X-Mailgun-Variables', json_encode(['my_message_id' => 123]));

        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('VerificationMailHandler::verify your email to ', ['error' => $e->getMessage()]);
        }
    }
}
