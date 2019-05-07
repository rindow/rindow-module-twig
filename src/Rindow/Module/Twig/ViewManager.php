<?php
namespace Rindow\Module\Twig;

/*use Rindow\Web\Mvc\ViewManager as ViewManagerInterface;*/

class ViewManager /* implements ViewManagerInterface */
{
    protected $serviceLocator;
    protected $config;
    protected $currentTemplatePaths;

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setStream($stream)
    {
    }

    public function setCurrentTemplatePaths($currentTemplatePaths)
    {
        $this->currentTemplatePaths = $currentTemplatePaths;
    }

    protected function getPostfix()
    {
        if(isset($this->config['postfix']))
            return $this->config['postfix'];
        else
            return '.twig.html';
    }

    protected function getTwig()
    {
        return TwigFactory::factory($this->serviceLocator);
    }

    public function render($templateName,array $variables=null,$templatePaths=null)
    {
        $twig = $this->getTwig();
        $loader = $twig->getLoader();
        if($templatePaths==null)
            $templatePaths = $this->currentTemplatePaths;
        $loader->setPaths($templatePaths);

        if(isset($this->config['layout'])) {
            $layout = $this->config['layout'];
        } else {
            $layout = 'layout/layout';
        }
        $variables['layout'] = $layout.$this->getPostfix();
        return $twig->render($templateName.$this->getPostfix(), $variables);
    }
}
