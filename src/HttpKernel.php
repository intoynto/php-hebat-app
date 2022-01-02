<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

use Intoy\HebatFactory\Kernel;
use Intoy\HebatFactory\Renderer\{HtmlErrorRenderer,JsonErrorRenderer};

use Intoy\HebatApp\Loaders\{
    LoaderSession,
    LoaderConfig,
    LoaderDatabase,
    LoaderLogger,
    LoaderMiddleware,
    LoaderProvider,
    LoaderView,
};

use Intoy\HebatApp\JWTMiddleware\JWTMiddleware;
use Intoy\HebatApp\Handlers\ShutdownHandler;

use Intoy\HebatApp\Middleware\{
    SessionMiddleware,
    BasePathMiddleware,
    GuardMiddleware,
    TrailingSlashMiddleware,
    CorsMiddleware,
    RouteContextMiddleware,

    TwigHelperMiddleware,
};

use Slim\Middleware\{BodyParsingMiddleware as SlimBodyParsingMiddleware,ErrorMiddleware as SlimErrorMiddleware};

class HttpKernel extends Kernel
{
    /**
     * Global loader
     * @var string[]
     */
    public $loaders=[
        LoaderSession::class,
        LoaderConfig::class,
        LoaderDatabase::class,
        LoaderLogger::class,
        LoaderView::class,
        LoaderMiddleware::class,
        LoaderProvider::class,
    ];

    /**
     * {@inheritdoc}
     */
    public array $middleware=[
        SessionMiddleware::class, //start first for session  
        TrailingSlashMiddleware::class, // redirect trailing slash
        CorsMiddleware::class,
        SlimBodyParsingMiddleware::class,
        RouteContextMiddleware::class, //register tracking input
    ];
    
    /**
     * {@inheritdoc}
     */
    public array $middlewareGroups=[
        'web'=>[
            GuardMiddleware::class,            
            \Slim\Views\TwigMiddleware::class, //default slim Twig middleware runtime extension
            TwigHelperMiddleware::class, // global var and Wbpack Extension  
        ],
        'api'=>[
           JWTMiddleware::class,
        ],
    ];


    /**
     * @return LoggerInterface|null
     */
    protected function resolveLogger()
    {
        $verbs=[
            LoggerInterface::class,
            "logger.app",
            "logger.web",
            "logger.api"
        ];
        $logger=null;
        foreach($verbs as $log)
        {
            if(app()->has($log))
            {
                $logger=app()->resolve($log);
                break;
            }
        }
        return $logger;
    }


    protected function resolveErrorMilddleware():SlimErrorMiddleware
    {
        $mid=app()->addErrorMiddleware(!is_production(),true,true);
        return $mid;
    }
	
	
	public function registerShutdownHandler(Request $request)
    {
        return;
        if($this->errorMiddleware)
        {
            $errorHandle=$this->errorMiddleware->getDefaultErrorHandler();
            if($errorHandle instanceof \Slim\Handlers\ErrorHandler)
            {
                $shutdownHandler=new ShutdownHandler($request, $errorHandle,!is_production());
                register_shutdown_function($shutdownHandler);
            }
        }
    }

    protected function onFinishSetup()
    {
        $mid=$this->resolveErrorMilddleware();
        $errorHandle=$mid->getDefaultErrorHandler();
        if($errorHandle instanceof \Slim\Handlers\ErrorHandler)
        {
            $contexts=[
                "text/html"=>HtmlErrorRenderer::class,
                "application/json",JsonErrorRenderer::class,
                "text/json",JsonErrorRenderer::class
            ];
            foreach(array_keys($contexts) as $type)
            {
                if(!in_array($type,array_keys($this->errorRenders)))
                {
                    $this->registerErrorRender($type,$contexts[$type]);
                }
            }

            foreach(array_keys($this->errorRenders) as $type)
            {
                $errorHandle->registerErrorRenderer($type,$this->errorRenders[$type]);
            }
        }
        app()->add(BasePathMiddleware::class);
    }
}