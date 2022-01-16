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
        "path" => ["/"],
        "ignore" => []
    ];

    /**
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }


    /**
     * Resolve path from basePath
     * @return string
     */
    protected function prefixPathWithBasePath(string $path)
    {
        $basePath=AppFactory::$app?AppFactory::$app->getBasePath():"";

        if($basePath)
        {
            $path=ltrim($path,$basePath);
            $basePath=rtrim($basePath);
            $path=rtrim($basePath,"/")."/".ltrim($path,"/");
        }

        return $path;
    }

    public function __invoke(Request $request): bool
    {
        $uri = "/" . $request->getUri()->getPath();
        $uri = preg_replace("#/+#", "/", $uri);
        /**
         * Path harus relative terhadap web sub folder
         * Contoh misalnya path perlu pengecekan authentikasi adalah path "api"
         * Dan folder web ada pada subfolder "my-app" maka path harus relative menjadi "my-app/api"
         * Jika web tidak berada pada sub-folder maka cukup "api" atau "/api"
         */

        /* If request path is matches ignore should not authenticate. */
        foreach ((array)$this->options["ignore"] as $ignore) {
            $ignore = rtrim($ignore, "/");

            //if($ignore!=="/") { $ignore="/".ltrim($ignore,"/"); }
            $ignore=$this->prefixPathWithBasePath($ignore);

            if (!!preg_match("@^{$ignore}(/.*)?$@", (string) $uri)) 
            {
                return false;
            }
        }

        /* Otherwise check if path matches and we should authenticate. */
        foreach ((array)$this->options["path"] as $path) {
            $path = rtrim($path, "/");

            $path=$this->prefixPathWithBasePath($path);

            if (!!preg_match("@^{$path}(/.*)?$@", (string) $uri)) 
            {               
                return true;
            }
        }

        return false;
    }
}