<?php

namespace Moukail\VerificationMailBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Moukail\VerificationMailBundle\DependencyInjection\MoukailVerificationMailExtension;

class MoukailVerificationMailBundle extends Bundle
{
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new MoukailVerificationMailExtension();
        }

        return $this->extension;
    }
}
