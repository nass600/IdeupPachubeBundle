<?php

namespace Ideup\PachubeBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;

/**
 * IdeupPachubeBundle Dependency Injection Extension
 *
 * Class that defines the Dependency Injection Extension to expose the bundle's semantic configuration
 * @package IdeupPhplistBundle
 * @subpackage DependencyInjection
 * @author Ignacio Velázquez Gómez <ivelazquez85@gmail.com>
 */
class IdeupPachubeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {       
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        // registering services
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        // Parameters
//        $container->setParameter('ideup_phplist.path', $config['admin_index_path']);
//        $container->setParameter('ideup_phplist.server_from', $config['server_from']);
//        $container->setParameter('ideup_phplist.tmp_directory', $config['tmp_directory']);
        
    }
    
    public function getAlias()
    {
        return 'ideup_pachube';
    }
}
