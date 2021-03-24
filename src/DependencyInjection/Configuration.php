<?php

namespace Moukail\VerificationMailBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('moukail_verification_mail');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('user_repository')
                    ->isRequired()
                    ->info('A class that implements UserRepositoryInterface - usually your UserRepository.')
                ->end()
                ->scalarNode('email_base_url')
                    ->isRequired()
                    ->info('Base email url')
                ->end()
                ->scalarNode('from_address')
                    ->isRequired()
                    ->info('From address')
                ->end()
                ->scalarNode('from_name')
                    ->isRequired()
                    ->info('From name')
                ->end()
                ->integerNode('lifetime')
                    ->defaultValue(3600)
                    ->info('The length of time in seconds that a password reset request is valid for after it is created.')
                ->end()
                ->integerNode('throttle_limit')
                    ->defaultValue(3600)
                    ->info('Another password reset cannot be made faster than this throttle time in seconds.')
                ->end()
                ->booleanNode('enable_garbage_collection')
                    ->defaultValue(true)
                ->info('Enable/Disable automatic garbage collection.')
            ->end();

        return $treeBuilder;
    }
}
