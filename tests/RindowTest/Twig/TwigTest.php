<?php
namespace RindowTest\Twig\TwigTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Cache\CacheFactory;
use Rindow\Container\ModuleManager;
use Rindow\Container\Container;
use Rindow\Web\Mvc\Router;
use Rindow\Web\Http\Request;
use Rindow\Web\Http\Response;
use Rindow\Web\Form\Element\ElementCollection;
use Rindow\Web\Form\Element\Element;

// Test Target Classes
use Twig_Environment;
use Twig_Loader_Array;
use Twig_Loader_Filesystem;
use Rindow\Module\Twig\Extension\Url;

class TestTranslator
{
    public function __construct($serviceManager=null)
    {
        $this->serviceManager = $serviceManager;
    }
    public function translate($message, $domain=null, $locale=null)
    {
        if($domain)
            $domain = ':'.$domain;
        else
            $domain = '';
        return '(translate:'.$message.$domain.')';
    }
}

class TestLogger
{
    protected $log = array();
    public function debug($message)
    {
        $this->log[] = $message;
    }
    public function getLog()
    {
        return $this->log;
    }
}

class TestSender
{
    protected $logger;

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function send($response)
    {
        $this->logger->debug(sprintf('HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $this->logger->debug($name.':'.$value);
            }
        }

        $this->logger->debug(strval($response->getBody()));
    }
}

class TestHandlerWithViewImplicite
{
    public function __invoke($request,$response,array $args)
    {
        return $args;
    }
}


class Test extends TestCase
{
    protected static $RINDOW_TEST_RESOURCES;

    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/resources';
    }

    public static function tearDownAfterClass()
    {
    }

    public function setUp()
    {
        $cache = new \Rindow\Stdlib\Cache\SimpleCache\FileCache(array('path'=>RINDOW_TEST_CACHE));
        $cache->clear();
    }

    public function testInit()
    {
        $loader = new Twig_Loader_Array(array(
            'index.html' => 'Hello {{ name }}!',
        ));
        $twig = new Twig_Environment($loader);

        $out = $twig->render('index.html', array('name' => 'Tom'));
        $this->assertEquals('Hello Tom!', $out);
    }

    public function testInit2()
    {
        $loader = new Twig_Loader_Filesystem(self::$RINDOW_TEST_RESOURCES .'/twig/templates');
        $twig = new Twig_Environment($loader, array(
            'cache' => RINDOW_TEST_CACHE .'/twig',
        ));
        $out = $twig->render('index/index.twig.html', array('name' => 'Tom'));
        $this->assertEquals("Hello Tom!\n", $out);
    }

    public function testNamespace()
    {
        $loader = new Twig_Loader_Filesystem();
        $loader->addPath(self::$RINDOW_TEST_RESOURCES.'/twig/templates/namespace1','n1');
        $loader->addPath(self::$RINDOW_TEST_RESOURCES.'/twig/templates/namespace2','n2');
        $loader->addPath(self::$RINDOW_TEST_RESOURCES.'/twig/templates/shared');
        $twig = new Twig_Environment($loader, array(
            'cache' => RINDOW_TEST_CACHE .'/twig',
        ));
        $out = $twig->render('@n1/index/index.twig.html');
        $this->assertEquals("[layout:Namespace1]Namespace1\n", $out);
        $out = $twig->render('@n2/index/index.twig.html');
        $this->assertEquals("[layout:Namespace2]Namespace2\n", $out);
        //$out = $twig->render('@n2/index/second.html');
        //echo $out;
    }

    public function testModuleView()
    {
        $config = array(
            'twig' => array(
                'cache'    => RINDOW_TEST_CACHE.'/twig',
            ),
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Module\Twig\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'mvc' => array(
                'plugins' => array(
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $sm = $moduleManager->getServiceLocator();
        $config = $sm->get('config');
        $viewManager = $sm->get('Rindow\Module\Twig\DefaultViewManager');

        $out = $viewManager->render('index/index',array('name'=>'Taro'),self::$RINDOW_TEST_RESOURCES.'/twig/templates');
        $this->assertEquals("Hello Taro!\n", $out);
    }

    public function testModule()
    {
        $config = array(
            'twig' => array(
                'cache'    => RINDOW_TEST_CACHE.'/twig',
                'template_paths' => self::$RINDOW_TEST_RESOURCES.'/twig/templates',
            ),
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Module\Twig\Module' => true,
                ),
                'enableCache'=>false,
            ),
        );
        $moduleManager = new ModuleManager($config);
        $twig = $moduleManager->getServiceLocator()->get('Twig_Environment');

        $out = $twig->render('index/index.twig.html', array('name' => 'Tom'));
        $this->assertEquals("Hello Tom!\n", $out);
    }

    public function testUrl()
    {
        $namespace = 'ABC';
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                    'Rindow\Module\Twig\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'container' => array(
                'aliases' => array(
                    'Rindow\\Web\\Mvc\\DefaultServerRequest' => 'Rindow\\Web\\Http\\Message\\TestModeServerRequest',
                ),
            ),
            'web' => array(
                'router' => array(
                    'routes' => array(
                        $namespace.'\home' => array(
                            'path' => '/test',
                            'defaults' => array(
                                'controller' => 'Index',
                                'action' => 'index',
                            ),
                            'type' => 'segment',
                            'parameters' => array('action', 'id'),
                            'namespace' => $namespace,
                        ),
                        $namespace.'\boo' => array(
                            'path' => '/boo',
                            'type' => 'literal',
                            'namespace' => $namespace,
                        ),
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $env = $sm->get('Rindow\Web\Http\Message\TestModeEnvironment');
        $env->_SERVER['SCRIPT_NAME'] = '/app/web.php';
        $env->_SERVER['REQUEST_URI'] = '/app/web.php/boo';
        $urlGenerator = $sm->get('Rindow\Web\Mvc\DefaultUrlGenerator');
        $request = $sm->get('Rindow\Web\Mvc\DefaultServerRequest');
        $router = $sm->get('Rindow\Web\Mvc\DefaultRouter');
        $urlGenerator->setRequest($request);
        $urlGenerator->setRouteInfo($router->match($request,$urlGenerator->getPath()));

        $loader = new Twig_Loader_Array(array(
            'url.html' => '{{ url( "home" ) }}',
            'url_action.html' => '{{ url( "home", {"action":"act","id":"id1"} ) }}',
            'url_action_query.html' => '{{ url( "home", {"action":"act","id":"id1"}, {"query":{"a":"b"}} ) }}',
            'url_frompath.html' => '{{ url_frompath( "/abc", {"query":{"a":"b"}} ) }}',
            'root.html' => '{{ url_root() }}',
            'prefix.html' => '{{ url_prefix() }}',
        ));
        $twig = new Twig_Environment($loader);
        $twig->addExtension($sm->get('Rindow\Module\Twig\Extension\DefaultUrl'));

        $out = $twig->render('url.html', array());
        $this->assertEquals('/app/web.php/test', $out);

        $out = $twig->render('url_action.html', array());
        $this->assertEquals('/app/web.php/test/act/id1', $out);

        $out = $twig->render('url_action_query.html', array());
        $this->assertEquals('/app/web.php/test/act/id1?a=b', $out);

        $out = $twig->render('url_frompath.html', array());
        $this->assertEquals('/app/web.php/abc?a=b', $out);

        $out = $twig->render('root.html', array());
        $this->assertEquals('/app/web.php', $out);

        $out = $twig->render('prefix.html', array());
        $this->assertEquals('/app', $out);
    }

    public function testForm()
    {
        $form = new ElementCollection();
        $form->type = 'form';

        $element = new Element();
        $element->type = 'text';
        $element->name = 'boo';
        $element->value = 'value';
        $element->label = 'LABEL';
        $form[$element->name] = $element;

        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Form\Module' => true,
                    'Rindow\Module\Twig\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'container' => array(
                'components' => array(
                    'Rindow\Web\Form\View\DefaultFormRenderer' => array(
                        'constructor_args' => array(
                            'translator' => array('ref' => __NAMESPACE__.'\TestTranslator'),
                        ),
                    ),
                    __NAMESPACE__.'\TestTranslator'=>array(),
                ),
            ),
            'twig' => array(
                'cache' => RINDOW_TEST_CACHE.'/twig',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $sm = $moduleManager->getServiceLocator();
        $viewManager = $sm->get('Rindow\Module\Twig\DefaultViewManager');

        $variables = array(
            'form' => $form,
        );
        $templateName = 'index/form';
        $templatePaths = array(self::$RINDOW_TEST_RESOURCES.'/twig/templates');
        $result = <<<EOT
<label>(translate:LABEL)</label>
<input type="text" value="value" name="boo">
EOT;
        $result .= "\n";
        $result = str_replace("\r", "", $result);
        $content = $viewManager->render($templateName,$variables,$templatePaths);
        $this->assertEquals($result,$content);
    }

    public function testOnMvc()
    {
        $namespace = __NAMESPACE__;
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                    'Rindow\Module\Twig\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'container' => array(
                'aliases' => array(
                    'Rindow\Web\Mvc\DefaultSender'        => __NAMESPACE__.'\TestSender',
                    'Rindow\\Web\\Mvc\\DefaultServerRequest' => 'Rindow\\Web\\Http\\Message\\TestModeServerRequest',
                ),
                'components' => array(
                    __NAMESPACE__.'\TestSender' => array(
                        'properties' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\TestLogger'),
                        ),
                    ),
                    __NAMESPACE__.'\TestHandlerWithViewImplicite' => array(
                    ),
                    __NAMESPACE__.'\TestLogger'=>array(
                    ),
                ),
            ),
            'web' => array(
                'mvc' => array(
                    'unittest' => false,
                ),
                'router' => array(
                    'routes' => array(
                        $namespace.'\home' => array(
                            'path' => '/test',
                            'type' => 'segment',
                            'parameters' => array('name'),
                            'namespace' => $namespace,
                            'handler' => array(
                                'callable' => __NAMESPACE__.'\TestHandlerWithViewImplicite',
                            ),
                            'view' => 'index/index',
                            'middlewares' => array(
                                'view' => 1,
                            ),
                        ),
                    ),
                ),
                'view' => array(
                    'view_managers' => array(
                        $namespace => array(
                            'template_paths' => array(
                                self::$RINDOW_TEST_RESOURCES.'/twig/templates',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $env = $sm->get('Rindow\Web\Http\Message\TestModeEnvironment');
        $env->_SERVER['SCRIPT_NAME'] = '/app/web.php';
        $env->_SERVER['REQUEST_URI'] = '/app/web.php/test/foo';
        $app = $sm->get('Rindow\Web\Mvc\DefaultApplication');
        $app->run();
        $logger = $sm->get(__NAMESPACE__.'\TestLogger');
        $answer = array(
            'HTTP/1.1 200 OK',
            'Hello foo!'."\n",
        );
        $this->assertEquals($answer,$logger->getLog());
    }
}
