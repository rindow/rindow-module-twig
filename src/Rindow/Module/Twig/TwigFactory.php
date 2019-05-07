<?php
namespace Rindow\Module\Twig;

use Twig_Loader_Filesystem;
use Twig_Environment;

class TwigFactory
{
    public static function factory(/*ContainerInterface*/ $serviceManager)
    {
        $loadedExtensions = array();

        $config = $serviceManager->get('config');
        $config = $config['twig'];
        if(isset($config['template_paths']))
            $paths = $config['template_paths'];
        else
            $paths = array();
        $loader = new Twig_Loader_Filesystem($paths);
        $twig = new Twig_Environment($loader, $config);

        if(isset($config['extensions'])) {
            foreach($config['extensions'] as $extension) {
                if(!$extension)
                    continue;
                if(isset($loadedExtensions[$extension]))
                    continue;
                if($serviceManager->has($extension)) {
                    $instance = $serviceManager->get($extension);
                    $twig->addExtension($instance);
                }
                $loadedExtensions[$extension] = true;
            }
        }

        return $twig;
    }
}