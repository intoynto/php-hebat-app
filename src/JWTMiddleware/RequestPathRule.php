<?php

declare (strict_types=1);


namespace Intoy\HebatApp\JWTMiddleware;


use Psr\Http\Message\ServerRequestInterface as Request;
use Intoy\HebatFactory\AppFactory;

final class RequestPathRule implements RuleInterface
{
    /**
     * Stores all the options passed to the rule
     * @var mixed[]
     */
    private $options = [
        'path' => ['/'],
        'ignore' => []
    ];

    /**
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $fullString
     * @param  string|string[]  $substr
     * @return bool
     */
    protected static function startsWith($fullString, $substr)
    {
        foreach ((array) $substr as $needle)
        {
            if ((string) $needle !== '' && strncmp($fullString, $needle, strlen($needle)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve path from basePath
     * @return string
     */
    protected function prefixPathWithBasePath(string $path)
    {
        $basePath=AppFactory::$app?AppFactory::$app->getBasePath():"";
        $origin_basePath=$basePath;  
        $origin_path=$path;      

        if($basePath && $basePath!=='/')
        {            
            $basePath=ltrim(rtrim($basePath,'/'),'/');
            $path=ltrim(rtrim($path,'/'),'/');

            // check if path not assigned basepath
            if(!static::startsWith($path,$basePath))
            {
                $path=$basePath.'/'.$path;
            }

            if(!static::startsWith($path,'/'))
            {
                $path='/'.$path;
            }            
        }          
        

        return $path;
    }

    public function __invoke(Request $request): bool
    {
        $uri = '/' . $request->getUri()->getPath();
        $uri = preg_replace('#/+#', '/', $uri);
        
        /**
         * Path harus relative terhadap web sub folder
         * Contoh misalnya path perlu pengecekan authentikasi adalah path "api"
         * Dan folder web ada pada subfolder "my-app" maka path harus relative menjadi "my-app/api"
         * Jika web tidak berada pada sub-folder maka cukup "api" atau "/api"
         */

        /* If request path is matches ignore should not authenticate. */
        
        foreach ((array)$this->options['ignore'] as $path) {
            $path = rtrim($path, '/');

            $path=$this->prefixPathWithBasePath($path);    
            
            // normalize path and url
            if(static::startsWith($uri,'/'))
            {
                if(!static::startsWith($path,'/'))
                {
                    $path='/'.$path;
                }
            }
            elseif(!static::startsWith($uri,'/'))
            {
                if(static::startsWith($path,'/'))
                {
                    $path=ltrim($path,'/');
                }
            }

            if (!!preg_match("@^{$path}(/.*)?$@", (string) $uri)) 
            {
                return false;
            }
        }

        /* Otherwise check if path matches and we should authenticate. */        
        foreach ((array)$this->options['path'] as $path) 
        {
            $path = rtrim($path, '/'); 

            $path=$this->prefixPathWithBasePath($path);  
            
            // normalize path and url
            if(static::startsWith($uri,'/'))
            {
                if(!static::startsWith($path,'/'))
                {
                    $path='/'.$path;
                }
            }
            elseif(!static::startsWith($uri,'/'))
            {
                if(static::startsWith($path,'/'))
                {
                    $path=ltrim($path,'/');
                }
            }
            
            if (!!preg_match("@^{$path}(/.*)?$@", (string) $uri)) 
            {                   
                return true;
            }
        }

        return false;
    }
}