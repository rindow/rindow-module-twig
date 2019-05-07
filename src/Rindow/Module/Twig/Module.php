<?php
namespace Rindow\Module\Twig;

class Module
{
    public function getConfig()
    {
        return array(
            'container' => array(
                'aliases' => array(
                    'Rindow\\Web\\Mvc\\DefaultViewManager' => 'Rindow\\Module\\Twig\\DefaultViewManager',
                ),
                'components' => array(
                    'Twig_Environment' => array(
                        'class' => 'Twig_Environment',
                        'factory' => 'Rindow\\Module\\Twig\\TwigFactory::factory',
                    ),
                    'Rindow\\Module\\Twig\\DefaultViewManager' => array(
                        'class' => 'Rindow\\Module\\Twig\\ViewManager',
                        'properties' => array(
                            'serviceLocator' => array('ref'=>'ServiceLocator'),
                        ),
                    ),
                    'Rindow\\Module\\Twig\\Extension\\DefaultForm' => array(
                        'class' => 'Rindow\\Module\\Twig\\Extension\\Form',
                        'properties' => array(
                            'serviceLocator' => array('ref'=>'ServiceLocator'),
                            'formRendererName' => array('value'=>'Rindow\\Web\\Form\\View\\DefaultFormRenderer'),
                        ),
                        'scope' => 'prototype',
                    ),
                    'Rindow\\Module\\Twig\\Extension\\DefaultUrl' => array(
                        'class' => 'Rindow\\Module\\Twig\\Extension\\Url',
                        'properties' => array(
                            'serviceLocator' => array('ref'=>'ServiceLocator'),
                            'urlGeneratorName' => array('value'=>'Rindow\\Web\\Mvc\\DefaultUrlGenerator'),
                        ),
                        'scope' => 'prototype',
                    ),
                ),
            ),
            'twig' => array(
                'extensions' => array(
                    'Form' => 'Rindow\\Module\\Twig\\Extension\\DefaultForm',
                    'Url' => 'Rindow\\Module\\Twig\\Extension\\DefaultUrl',
                ),
            ),
        );
    }
}
