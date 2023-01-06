<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

use Intoy\HebatFactory\Kernel;
use Intoy\HebatApp\Renderer\{HtmlErrorRenderer,JsonErrorRenderer};

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

/**
 * Petunjuk penggunaan Middleware
 * -------------------------------
 * Middleware adalah lapisan konsentris yang mengelilingi aplikasi inti.
 * Struktur konsentris meluas ke luar saat lapisan middleware baru ditambahkan.
 * 
 * Proses objek Request melintasi struktur middleware dari luar ke dalam.
 * Request akan memasuki middleware terluar, lalu middleware berikutnya, dan seterusnya, hingga akhirnya tiba di aplikasi inti.
 * Setelah aplikasi inti memproses rute yang sesuai, objek Response yang dihaslikan melintasi struktur middleware dari dalam ke luar. 
 * Objek Reponse akhir keluar dari middleware terluar, diserialisasi menjadi Response HTTP Mentah, dan dikembalikan ke client HTTP.   
 * 
 * Jika diilustrasikan, misalnya terdapat 2 middleware dengan urutan :
 * 1. A
 * 2. B
 * 3. C
 * Proses request akan melewati middleware C, kemudian middleware B, middleware A, lanjut sampai ke aplikasi inti.
 * Aplikasi inti akan memproses Request dan mengembalikan Response.
 * Objek Response yang dihasilkan oleh aplikasi inti, akan melewati middleware A, kemudian middleware B, terakhir middleware C, 
 * sampai akhirnya object Response diserialisasi menjadi Response HTTP mentah untuk client HTTP.
 * Middleware A adalah bagian terdalam, Middleware C adalah middleware bagian terluar.
 * 
 * Untuk lebih lengkapnya lihat :
 * https://www.slimframework.com/docs/v4/concepts/middleware.html 
 */

class HttpKernel extends Kernel
{
    /**
 * Global loader
 * @var string[]
 */
    public $loaders=[
        LoaderConfig::class,
        LoaderLogger::class,
        LoaderSession::class,
        LoaderDatabase::class,
        LoaderView::class,
        LoaderMiddleware::class,
        LoaderProvider::class,
    ];

    /**
     * {@inheritdoc}
     */
    public array $middleware=[
        CorsMiddleware::class,
        RouteContextMiddleware::class, //register tracking input
        SlimBodyParsingMiddleware::class,
        SessionMiddleware::class, //start first for session  
        TrailingSlashMiddleware::class, // redirect trailing slash
    ];
    
    /**
     * {@inheritdoc}
     */
    public array $middlewareGroups=[
        'web'=>[
            TwigHelperMiddleware::class, // global var and Wbpack Extension  
            \Slim\Views\TwigMiddleware::class, //default slim Twig middleware runtime extension
            GuardMiddleware::class,            
        ],
        'api'=>[
           JWTMiddleware::class,
        ],
    ];


    /**
     * varibale is registered shutdown handler
     * @var bool
     */
    protected $isRegisteredShutdownHandler=false;


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

    /**
     * callable yang akan dipanggil 
     * oleh app ketika akan menjalankan request
     */
	public function registerShutdownHandler(Request $request)
    {
        if($this->isRegisteredShutdownHandler || !$this->useShutdownHandler) return;

        if($this->useShutdownHandler)
        {
            $mid=$this->resolveErrorMilddleware();
            $errorHandle=$mid->getDefaultErrorHandler();
            if($errorHandle instanceof \Slim\Handlers\ErrorHandler)
            {
                $errorHandle->forceContentType('text/html');
                $shutdownHandler=new ShutdownHandler($request, $errorHandle,!is_production());
                register_shutdown_function($shutdownHandler);
            }
            $this->isRegisteredShutdownHandler=true;
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
                "application/json"=>JsonErrorRenderer::class,
                "text/json"=>JsonErrorRenderer::class
            ];
            foreach($contexts as $contextType => $render)
            {                
                if(!in_array($contextType,array_keys($this->errorRenders)))
                {                    
                    $this->registerErrorRender($contextType,$render);
                }
            }

            foreach($this->errorRenders as $contextType => $render)
            {
                $errorHandle->registerErrorRenderer($contextType,$render);
            }

            //set default error render
            $errorHandle->setDefaultErrorRenderer("text/html",HtmlErrorRenderer::class);
        }
        // add base path middleware
        app()->add(BasePathMiddleware::class);
    }
}