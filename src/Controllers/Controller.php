<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Controllers;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Intoy\HebatDatabase\Model;
use Intoy\HebatDatabase\Query\Builder;
use Intoy\HebatApp\InputFormRequest;
use Intoy\HebatApp\Helper;

class Controller {
    /**
     * Container
     * @var Container
     */
    protected $container;


    public function __construct(Container $c)
    {        
        $this->container=$c;
        $this->onCreated();
    }

    protected function onCreated()
    {

    }

    /**
     * Magic resolve 
     */
    public function __get($name)
    {
        if($this->container->has($name)){
            return $this->container->get($name);
        }
    }

    /**
     * Resolve component or data from container
     * @param string $id Identifier of the entry to look for.
     * @return mixed entry
     */   
    protected function containerGet($id)
    {
        return $this->container->has($id)?$this->container->get($id):null;
    }    

    /**
     * @return Twig
     */
    protected function twig()
    {
        return $this->container->get(Twig::class);
    }

    /**
     * Get collection item for key
     * @param string $key The data key
     * @return mixed The key's value, or the default value
     */
    protected function dataGet($key)
    {
        return $this->twig()->offsetGet($key);
    }

    /**
     * Set collection item
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    protected function dataSet($key,$value)
    {
        $this->twig()->offsetSet($key,$value);
    }

    /**
     * Does this collection have a given key?
     * @param  string $key The data key
     * @return bool
     */
    protected function dataExists($key)
    {
        return $this->twig()->offsetExists($key);
    }


    /**
     * Remove item from collection
     * @param string $key The data key
     */
    protected function dataUnset($key)
    {
        if($this->dataExists($key)) $this->twig()->offsetUnset($key);
    }


    /**
     * fetch data into twig template
     * @param string $template
     * @param mixed $data
     * @return string
     */
    protected function fetchView($template, $data=[])
    {
        return $this->twig()->fetch($template,$data??[]);
    }

    /**
     * make fetch data to body response
     * @param string $template
     * @param mixed $data
     * @param Response $response
     * @return Response 
     */
    protected function view($template,$data=[],$response=null)
    {
        $response=$response?$response:response();
        $response->getBody()->write($this->fetchView($template,$data));
        return $response;
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param mixed|array $data
     * @param string $template
     * @return Response
     */
    protected function make404($request,$response,$data=[],$template="errors/default")
    {
        if(!is_value(data_get($data,"code"))) { data_set($data,"code",404); }
        if(!is_value(data_get($data,"title"))) { data_set($data,"title","Not Found"); }
        if(!is_value(data_get($data,"message"))) { data_set($data,"message","Ups!! The page you are looking for might have been removed had its name changed or is temporaly unavailable"); }

        $code=data_get($data,"code");
        $is_json=Helper::determineContentJson($request);
        if($is_json)
        {       
            // send back json not found
            $response->getBody()->write(json_encode($data));
            return $response
                    ->withHeader('content-type','application.json')
                    ->withStatus($code)
                    ;
        }

        return $this->view($template,["error"=>$data])->withStatus($code);
    }


    /**
     * make response with json
     * @param string $message
     * @param int $statusCode default 422 
     * @param Response|null $response
     * @return Response
     */
    protected static function makeInvalidAsJson($message, $statusCode=422, $response=null)
    {
        $response=$response?$response:response();
        $response->getBody()->write(json_encode(compact("statusCode","message")));
        return $response
                    ->withStatus($statusCode)
                    ->withHeader("content-type","application/json");
        
    }

    /**
     * make response with json
     * @param string $message
     * @param Response|null $response
     * @return Response
     */
    protected static function makeJson($data=null, $response=null)
    {
        $response=$response?$response:response();
        $response->getBody()->write(json_encode($data));
        return $response
                    ->withHeader("content-type","application/json");
        
    }

    /**
     * Get InputFormRequest::class from container
     * @param string $class of target class
     * @return InputFormRequest
     */
    protected function resolveInput(string $class=InputFormRequest::class)
    {
        return $this->containerGet($class);
    }   

    /**
     * handle input 
     * @param Response $response
     * @param InputFormRequest $input
     * @return bool
     */
    protected static function handleInput(&$response, $input)
    {
        if($input->failed())
        {        
            $code=422;
            $message=$input->getErrorFirst();
            $response->getBody()->write(json_encode(compact("code","message")));
            $response=$response->withStatus($code)
                     ->withHeader("content-type","application/json");
            return false;
        }
        return true;
    }


    /**
     * @param Model|string $classModel Reflection Model class
     * @param array $fillable initialize fillable
     * @return Model
     */
    protected static function modelResolve($classModel,$fillable=[])
    {
        return new $classModel($fillable);
    }

    /**
     * Generate query builder default
     * @param string $classModel Reflection Model class
     * @return Builder
     */
    protected function modelQuery($classModel)
    {        
        $obj=static::modelResolve($classModel);
        [$table,$columns]=$obj->getTableOrView();
        $builder=$obj::connection()->query()->select($columns)->from($table);
        return $builder;
    }
}