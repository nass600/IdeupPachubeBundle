<?php

namespace Ideup\PachubeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Ignacio Velázquez Gómez <ivelazquez85@gmail.com>
 */
class Configuration implements ConfigurationInterface
{    
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ideup_pachube');

//        $rootNode
//            ->children()
//                ->scalarNode('admin_index_path')
//                    ->defaultValue('/var/www/phplist/public_html/lists/admin/index.php')
//                ->end()
//                ->scalarNode('server_from')
//                    ->defaultValue('me@server.com')
//                ->end()
//                ->scalarNode('tmp_directory')
//                    ->defaultValue('/tmp')
//                ->end()
//            ->end()
//        ;

        return $treeBuilder;
    }
}
