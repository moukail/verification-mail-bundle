<?php

namespace Moukail\VerificationMailBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class MoukailVerificationMailExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(\dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.xml');

        //$loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__) .'/Resources/config'));
        //$loader->load('services.yml');

        $container->setParameter('moukail_verification_mail.email_base_url', $config['email_base_url']);

        $helperDefinition = $container->getDefinition('moukail_verification_mail.verification_mail_controller');
        $helperDefinition->replaceArgument(1, new Reference($config['user_repository']));

        $helperDefinition = $container->getDefinition('moukail_verification_mail.verification_mail_handler');
        $helperDefinition->replaceArgument(2, $config['from_address']);
        $helperDefinition->replaceArgument(3, $config['from_name']);
        //$helperDefinition->replaceArgument(4, new Reference($config['email_base_url']));

    }

    public function getAlias()
    {
        return 'moukail_verification_mail';
    }
}
