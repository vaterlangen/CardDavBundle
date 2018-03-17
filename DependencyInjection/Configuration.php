<?php

namespace vaterlangen\CardDavBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('vaterlangen_card_dav');

        $rootNode
        	->children()
        		->scalarNode('enabled')->defaultValue(false)->end()
        		->arrayNode('addressbooks')
        			->useAttributeAsKey('id')
        			->prototype('array')
        				->children()
			        		->scalarNode('server')->isRequired()->end()
			        		->scalarNode('ssl')->defaultValue(true)->end()
			        		->integerNode('port')->defaultValue(0)->end()
			        		->scalarNode('user')->isRequired()->end()
			        		->scalarNode('email')->defaultValue(NULL)->end()
			        		->scalarNode('password')->isRequired()->end()
			        		->scalarNode('resource')->isRequired()->end()
			        		->arrayNode('categories')
			        			->prototype('scalar')->end()
			        		->end()
			        	->end()
			        ->end()
			   	->end()
			->end()
        ;

        return $treeBuilder;
    }
}
