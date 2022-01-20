<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Renderer;

use Throwable;
use Intoy\HebatSupport\Str;
use Intoy\HebatFactory\Renderer\Traits\TrackError;
use Slim\Error\Renderers\JsonErrorRenderer as SlimJsonErrorRenderer;

class JsonErrorRenderer extends SlimJsonErrorRenderer
{
    use TrackError;

    /**
     * @var string
     */
    protected $defaultErrorTitle = 'Application Error';
    
    
    protected function resolveApplicationTitle():string
    {
        $title=config("app.name");
        return $title?$title:$this->defaultErrorTitle;
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    protected function getErrorTitle(Throwable $exception): string
    {
        if($string=$this->trackErrorTitle($exception))
        {
            return $string;
        }

        $string=$this->resolveApplicationTitle();
        if(!Str::contains($string,"error"))
        {
            $string.=" Error";
        }
        
        return $string;
    }


    /**
     * @param Throwable $exception
     * @return string|null
     */
    protected function getErrorMessage(Throwable $exception)
    {
        if($string=$this->trackErrorMessage($exception))
        {
            return $string;
        }
        return "The application could not run because of the following error";
        
        return null;
    }


    /**
     * @param Throwable $exception
     * @param bool      $displayErrorDetails
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $title=$this->getErrorTitle($exception);
        $message=$this->getErrorMessage($exception);
        $error = [
            'title'=>$title,
            'message'=>$message?:$title,
        ];

        if ($displayErrorDetails) {
            $error['exception'] = [];
            do {
                $error['exception'][] = $this->formatExceptionFragment($exception);
            } while ($exception = $exception->getPrevious());
        }

        return (string) json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param Throwable $exception
     * @return array<string|int>
     */
    protected function formatExceptionFragment(Throwable $exception): array
    {
        $errors=[
            'type' => get_class($exception),
            'code' => $exception->getCode(),
        ];
        $message=$exception->getMessage();
        if($string=$this->getErrorMessage($exception))
        {
            $message=$string;
        }

        $errors["message"]=$message;
        $errors["description"]=$exception->getMessage();
        $errors["file"]=$exception->getFile();
        $errors["line"]=$exception->getLine();

        return $errors;
    }
}