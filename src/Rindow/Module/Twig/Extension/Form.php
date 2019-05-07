<?php
namespace Rindow\Module\Twig\Extension;

use Twig_Extension;
use Twig_SimpleFunction;
use Rindow\Web\Form\ElementCollection;
use Rindow\Web\Form\Element;
use Rindow\Web\Form\View\FormRenderer;

class Form extends Twig_Extension
{
    protected $renderer;
    protected $theme;
    protected $translator;
    protected $textDomain;
    protected $serviceManagerOrRenderer;
    protected $formRendererName;

    public function __construct($serviceManagerOrRenderer=null,$theme=null,$translator=null,$textDomain=null)
    {
        $this->serviceManagerOrRenderer = $serviceManagerOrRenderer;
        $this->theme = $theme;
        $this->translator = $translator;
        $this->textDomain = $textDomain;
    }

    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;
    }

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceManagerOrRenderer = $serviceLocator;
    }

    public function setFormRendererName($formRendererName)
    {
        $this->formRendererName = $formRendererName;
    }

    public function getRenderer()
    {
        if($this->renderer)
            return $this->renderer;
        if($this->serviceManagerOrRenderer) {
            if($this->serviceManagerOrRenderer instanceof FormRenderer) {
                $this->renderer = $this->serviceManagerOrRenderer;
                $this->serviceManagerOrRenderer = null;
            } else {
                $this->renderer = $this->serviceManagerOrRenderer->get($this->formRendererName);
            }
        } else {
            $this->renderer = new FormRenderer($this->theme,$this->translator,$this->textDomain);
        }
        return $this->renderer;
    }

    public function getName()
    {
        return 'form';
    }

    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('form_open'   ,array($this,'open'),      array('is_safe' => array('html'))),
            new Twig_SimpleFunction('form_close'  ,array($this,'close'),     array('is_safe' => array('html'))),
            new Twig_SimpleFunction('form_widget' ,array($this,'widget'),    array('is_safe' => array('html'))),
            new Twig_SimpleFunction('form_label'  ,array($this,'label'),     array('is_safe' => array('html'))),
            new Twig_SimpleFunction('form_errors' ,array($this,'errors'),    array('is_safe' => array('html'))),
            new Twig_SimpleFunction('form_raw'    ,array($this,'raw'),       array('is_safe' => array('html'))),
            new Twig_SimpleFunction('form_theme'  ,array($this,'setTheme'),  array('is_safe' => array('html'))),
            new Twig_SimpleFunction('form_add'    ,array($this,'addElement'),array('is_safe' => array('html'))),
        );
    }

    public function open(ElementCollection $element,array $attributes=array())
    {
        return $this->getRenderer()->open($element,$attributes);
    }

    public function close(ElementCollection $element,array $attributes=array())
    {
        return $this->getRenderer()->close($element,$attributes);
    }

    public function label(Element $element,array $attributes=array())
    {
        return $this->getRenderer()->label($element,$attributes);
    }

    public function widget(Element $element,array $attributes=array())
    {
        return $this->getRenderer()->widget($element,$attributes);
    }

    public function errors(Element $element,array $attributes=array())
    {
        return $this->getRenderer()->errors($element,$attributes);
    }

    public function raw(Element $element,$attributes=array())
    {
        return $this->getRenderer()->raw($element,$attributes);
    }

    public function setTheme($theme)
    {
        return $this->getRenderer()->setTheme($theme);
    }

    public function addElement(ElementCollection $elementCollection,$type,$name,$value=null,$label=null,$attributes=null)
    {
        return $this->getRenderer()->addElement($elementCollection,$type,$name,$value,$label,$attributes);
    }
}
