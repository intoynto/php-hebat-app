<?php

declare(strict_types=1);

namespace Intoy\HebatApp;

use Intoy\HebatFactory\InputRequest;
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
}