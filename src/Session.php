<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Intoy\HebatFactory\Foundation\BaseSession;
use Intoy\HebatSupport\Validation\Validator;

class Session extends BaseSession
{
    /**
     * flash set
     * @param string $message 
     * @param string $key
     */
    public function flashAdd(string $message, $key="info")
    {
        $this->getFlash()->add($key,$message);
    }

    /**
     * flash set
     * @param string $key
     * @param string[] $messages
     */
    public function flashSet(array $messages,$key="info")
    {
        $this->getFlash()->set($key,$messages);
    }

    /**
     * flash set
     * @param string $key
     * @return mixed
     */
    public function flashGet(string $key="info")
    {
        return $this->getFlash()->get($key);
    }

    public function flashAll()
    {
        return $this->getFlash()->all();
    }
    
    public function validate($inputs, array $rules, array $alias):Validator
    {
        $validator=new Validator();
        $validator->make($inputs, $rules, $alias);
        $validator->validate();
        
        $this->flashSet($inputs,"old");

        if($validator->failed())
        {
            $this->flashSet($validator->getNotValidData(),"error");
            $this->save();
        }
        
        return $validator;
    }
}