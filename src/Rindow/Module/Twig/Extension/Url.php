<?php
namespace Rindow\Module\Twig\Extension;

use Twig_Extension;
use Twig_SimpleFunction;
use Rindow\Module\Twig\Exception;

class Url extends Twig_Extension
{
    protected $serviceLocator;
    protected $url;
    protected $urlGeneratorName;

    public function __construct($serviceLocator=null)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function setUrlGeneratorName($urlGeneratorName)
    {
        $this->urlGeneratorName = $urlGeneratorName;
    }

    public function setUrlGenerator($urlGenerator)
    {
        $this->url = $urlGenerator;
    }

    protected function getUrl()
    {
        if($this->url)
            return $this->url;
        if($this->serviceLocator==null)
            throw new Exception\DomainException("plugin manager is not specified.");
        if(!$this->serviceLocator->has($this->urlGeneratorName))
            throw new Exception\DomainException("urlGenerator is not specified.");
        return $this->url = $this->serviceLocator->get($this->urlGeneratorName);
    }

    public function getName()
    {
        return 'url';
    }

    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('url'          ,array($this,'fromRoute'), array('is_safe' => array('html'))),
            new Twig_SimpleFunction('url_frompath' ,array($this,'fromPath'),  array('is_safe' => array('html'))),
            new Twig_SimpleFunction('url_root'     ,array($this,'rootPath'),  array('is_safe' => array('html'))),
            new Twig_SimpleFunction('url_prefix'   ,array($this,'prefix'),    array('is_safe' => array('html'))),
        );
    }

    public function fromRoute($routeName,array $params=array(),$options=array())
    {
        return $this->getUrl()->fromRoute($routeName,$params,$options);
    }

    public function fromPath($path,$options=array())
    {
        return $this->getUrl()->fromPath($path,$options);
    }

    public function rootPath()
    {
        return $this->getUrl()->rootPath();
    }

    public function prefix()
    {
        return $this->getUrl()->prefix();
    }
}
