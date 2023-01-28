<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Intoy\HebatFactory\Foundation\BaseSession;
use Intoy\HebatSupport\Validation\{
    Validator,
    Validation,
};

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
    
    /**    
     * @return Validation
     */
    public function validate($inputs, array $rules, array $alias)
    {
        $validator=new Validator();
        $valton=$validator->make($inputs, $rules);
        $valton->setAliases($alias);
        $valton->validate();
        
        $this->flashSet($inputs,"old");

        if($valton->failed())
        {
            $this->flashSet($valton->getNotValidData(),"error");
        }
        
        return $valton;
    }
}