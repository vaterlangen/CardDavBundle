<?php

namespace vaterlangen\CardDavBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class vaterlangenCardDavExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
 public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        
        $addressbooks = isset($config['addressbooks']) ? $config['addressbooks'] : array();
        $container->setParameter('vaterlangen_card_dav.addressbooks', $addressbooks);
        $container->setParameter('vaterlangen_card_dav.enabled', $config['enabled']);
    }
}
