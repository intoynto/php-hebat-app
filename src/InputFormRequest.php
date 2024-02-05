<?php

declare(strict_types=1);

namespace Intoy\HebatApp;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\Route;
use Intoy\HebatFactory\InputRequest;
use Intoy\HebatDatabase\Model;
use Intoy\HebatSupport\Validation\{
    Validation,
    ErrorBag,
};

class InputFormRequest extends InputRequest
{
    /**
     * Validator
     * @var Validation
     */
    protected $validator;


    /**
     * @var string
     */
    protected $error="";

    /**
     * @var Request
     */
    protected $request;

    /**
     * @array
     */
    protected $_originAttributes=[];

    /**
     * @var string $classModel
     */
    protected $classModel;

    /**
     * @var mixed
     */
    protected $item;    

    /**
     * Constructor
     * @param Request $request
     * @param Route $route
     */
    public function __construct(Request $request, Route $route)
    {
        parent::__construct($request,$route);

        $this->request=$request; // store request  
        
        if($this->getIsFromArguments())
        {
            $arguments=data_get($this->_meta,"arguments",[]);
            $this->_attributes=is_array($arguments)?$arguments:[];
        }
        else 
        {
            $this->_attributes=$request->getParsedBody()??$request->getQueryParams();
        }
        
        $this->_originAttributes=$this->_attributes;
    }
    
    /**
     * @return string
     * jika mengambil dari arguments route
     * isi dengan "args" atau "arguments"
     */
    protected function getTargetAttributType()
    {
        return "";
    }

    /**
     * @return bool
     */
    protected function getIsFromArguments()
    {
        return in_array($this->getTargetAttributType(),['args','arguments']);
    }

    public function rules()
    {
        return [];
    }

    protected function alias()
    {
        return [];
    }

    public function validate()
    {
        $this->onBefore();
        $this->validator=session()->validate(
            $this->all(),
            $this->rules(),
            $this->alias(),
        );

        if($this->validator->failed())
        {
            $this->onFailed();
        }
        else {
            $this->applyAfterValidate();
            $this->onSuccess();
        }
        $this->onAfter();
    }
    
    /**
     * @return bool
     */
    public function failed():bool
    {
        if($this->validator && $this->validator->failed())
        {
            return true;
        }

        if(!empty($this->error))
        {
            return true;
        }
        return false;
    }

    /**
     * @return array|null
     */
    public function getInputErrors()
    {
        if($this->validator && $this->validator->failed())
        {
            $error=$this->validator->errors();
            if($error instanceof ErrorBag)
            {
                $errors=$error->toArray();
                $error=[];
                foreach($errors as $key => $value)
                {
                    $value=is_array($value)?array_values($value):$value;
                    $value=is_array($value)?implode(", ",$value):$value;
                    $error[$key]=$value;
                }
                $error=count($error)?$error:null;
            }

            return $error;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getErrorFirst()
    {
        if($this->validator && $this->validator->failed())
        {
            $errorValues=$this->validator->errors()->firstOfAll();
            $errors=array_values($errorValues);  
            $info=(string)array_shift($errors);
            return $info;
        }

        return $this->error?:"";
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->all();
    }

    protected function onBefore()
    {

    }    

    protected function onFailed()
    {

    }

    protected function applyAfterValidate()
    {
        //reset attributes;
        $this->_attributes=[];
        foreach($this->validator->getNotValidData() as $key => $val)
        {
            $this->$key=null;
        }

        foreach($this->validator->getValidData() as $key => $val)
        {
            $this->$key=$val;
        }
    }

    protected function onSuccess()
    {

    }

    protected function onAfter()
    {
    }

    /**
     * Ambil data dari arguments route
     * @param string $name
     * @return mixed
     */
    protected function valueArgument($key, $default=null)
    {
        $arguments=data_get($this->_meta,'arguments');
        return data_get($arguments,$key,$default);
    }

    /**
     * Apakah request kali ini adalah proses update/editing
     * @return bool
     */
    protected function isEditing()
    {
        return false;
    }

    /**
     * Get current item 
     * @return stdClass|mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param string $classModel
     * @return Model
     */
    protected function newModel($classModel='')
    {
        if(!$classModel)
        {
            $classModel=$this->classModel;
        }
        return new $classModel();
    }

    /**
     * @param array $wheres fillable
     * @param string $wheres class model or protected classModel
     * @return stdClass|null
     */
    protected function loadModel(array $wheres, $classModel = "")
    {
        $classModel=!$classModel?$this->classModel:$classModel;
        if(!$classModel) return null;
        
        $obj=$this->newModel($classModel);
        [$table,$fiels]=$obj->getTableOrView();
        return $obj::connection()->query()->select($fiels)->from($table)->where($wheres)->take(1)->first();
    }

    /**
     * @param array $wheres for fillabel
     * @param string $classModel class model or protected classModel
     * @return Model|null
     */
    protected function loadModelRelation(array $wheres,$classModel='')
    {
        $classModel=!$classModel?$this->classModel:$classModel;
        if(!$classModel) return null;

        $obj=$this->newModel($classModel);
        return $obj::getRelation($wheres);
    }
}