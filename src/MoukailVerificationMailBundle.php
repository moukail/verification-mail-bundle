<?php

namespace Moukail\VerificationMailBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

use Moukail\VerificationMailBundle\DependencyInjection\MoukailVerificationMailExtension;

class MoukailVerificationMailBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new MoukailVerificationMailExtension();
        }

        return $this->extension;
    }
}
