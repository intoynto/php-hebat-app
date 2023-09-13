<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Intoy\HebatFactory\Foundation\BaseSession;
use Intoy\HebatSupport\Validation\Interfaces\{
    ValidatorInterface,
    ValidationInterface,
};
use Intoy\HebatSupport\Validation\{
    Validator,
};

class Session extends BaseSession
{
    /**
     * flash set
     * @param string $message 
     * @param string $key
     */
    public function flashAdd(string $message, $key='info')
    {
        $this->getFlash()->add($key,$message);
    }

    /**
     * flash set
     * @param string $key
     * @param string[] $messages
     */
    public function flashSet(array $messages,$key='info')
    {
        $this->getFlash()->set($key,$messages);
    }

    /**
     * flash set
     * @param string $key
     * @return mixed
     */
    public function flashGet(string $key='info')
    {
        return $this->getFlash()->get($key);
    }

    public function flashAll()
    {
        return $this->getFlash()->all();
    }

    /**
     * @return ValidatorInterface
     */
    protected static function resolveValidator()
    {
        if(function_exists('validator'))
        {
            try
            {
                $validator=call_user_func('validator');
                if($validator instanceof ValidatorInterface)
                {
                    return $validator;
                }
            }
            catch(\Exception $e)
            {

            }
        }
        else {
            // get in container
            try {
                if(app()->has(ValidatorInterface::class))
                {
                    $validator=app()->resolve(ValidatorInterface::class);
                    if($validator instanceof ValidatorInterface)
                    {
                        return $validator;
                    }
                }
            }
            catch(\Exception $e)
            {
                // no exception
            }
        }
        

        // no validator. but resolve default validator
        return new Validator();
    }
    
    /**    
     * @return ValidationInterface
     */
    public function validate($inputs, array $rules, array $alias)
    {
        $validator=static::resolveValidator();
        $valton=$validator->make($inputs, $rules);
        $valton->setAliases($alias);
        $valton->validate();
        
        $this->flashSet($inputs,'old');

        if($valton->failed())
        {
            $error=$valton->getInputErrors();
            if($error)
            {
                $this->flashSet($error,'error');
            }
        }
        
        return $valton;
    }
}