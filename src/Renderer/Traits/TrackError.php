<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Renderer\Traits;

use Throwable;
use PDOException;
use Slim\Exception\HttpException;
use Intoy\HebatDatabase\QueryException;
use Intoy\HebatSupport\Validation\Exceptions\{
    MissingRequiredParameterException
};

trait TrackError 
{
    protected $errorPdoDefs=[
        // postgres
        'P0001'=>'Database Exception',
        '0B000'=>'Inisialisasi transaksi database tidak valid',
        '22P02'=>'Representation text invalid',
        '23503'=>'Foreign key violation. Item masih terkait / berelasi atau terhubung dengan data lain',
        '23505'=>'Unique Violation. Attribut unik terduplikasi',
        '42703'=>'Undefined Column. Kolom dalam entitas tabel tidak  terdefinisi',
        '42883'=>'Undefined function. Nama fungsi / prosedur dan argumen tidak tersedia dalam database',

        // mysql
        '23000'=>'Foreign key violation. Item masih terkait / berelasi atau terhubung dengan data lain',
    ];

    /**
     * @param Throwable $exception
     * @return string|null
     */
    protected function trackErrorTitle(Throwable $throw)
    {
        $string=null;
        if($throw instanceof PDOException)
        {
            return 'Database Exception';
        }

        return $string;
    }


    /**
     * @param Throwable $exception
     * @return string|null
     */
    protected function trackErrorMessage(Throwable $e)
    {
        $string=null;
        if($e instanceof HttpException)        
        {
            return $e->getMessage();
        }

        if($e instanceof MissingRequiredParameterException)
        {
            return $e->getMessage();
        }

        if($e instanceof PDOException)
        {            
            $code=(string)$e->getCode();
            $message='Database Exception';
            if(in_array($code,array_keys($this->errorPdoDefs)))
            {
                $is_query_exception=$e instanceof QueryException;
                $message=$is_query_exception?$e->getPrevious()->getMessage():$e->getMessage();
                $naturalMessage=$e instanceof QueryException?$e->getOriginMessage():$e->getMessage();

                if(in_array($code,['P0001','42703','42883'])) 
                {
                    // column not exists
                    if($code==='42703')  return $this->trackPdoErrorColumnNotExists($message);
                    
                    // function not exists
                    if($code==='42883') return $this->trackPdoErrorFuncNotExists($message);

                    // return default 
                    return $this->trackPdoErrorDefault($naturalMessage);
                }               

                return $this->errorPdoDefs[$code];
            }
            elseif(strstr($e->getMessage(), 'SQLSTATE[')) 
            { 
                preg_match('/SQLSTATE\[(\w+)\](.*)/', $e->getMessage(), $matches);  
                if(isset($matches[1]))
                {
                    if($matches[1]==='08006')
                    {
                        $code=$matches[1];
                        $message='Failure db connection';
                    }
                    elseif($matches[1]==='42P01')
                    {
                        $code=$matches[1];
                        $message='Table or view undefined';
                    }
                }
            }
            return implode(' ',[$message,'#'.$code.'.']);
        }

        return $string;
    }


    /**
     * @param string $message
     * @return string
     */
    protected function trackPdoErrorDefault(string $message)
    {
        //"SQLSTATE[42703]: Undefined column: 7 ERROR:  ...errormessage\nHINT ...hint_message
        
        $origin=$message;
        $message=explode('\n',$message);
        $first=array_shift($message);

        $first=explode('ERROR',$first);
        array_shift($first);
        $first=array_shift($first);
        $first=trim((string)$first);
        $first=ltrim($first,":");
        $first=trim((string)$first);

        $message=array_shift($message);
        $message=trim((string)$message);
        $message=explode('HINT',$message);
        $message=count($message)>1?$message[1]:$message[0];
        $message=trim((string)$message);
        $message=ltrim($message,':');
        $message=trim((string)$message);

        $info=[
            'P0001 Database Exception',
            $first
        ];
        if(strlen($message)>0){
            $info[]=$message;
        }
        return implode(". ",$info);
    }

    /**
     * @param string $message
     * @return string
     */
    protected function trackPdoErrorColumnNotExists(string $message)
    {
        //"SQLSTATE[42703]: Undefined column: 7 ERROR:  column \"tahun\" of relation \"rek_kelompok\" does not exist\nLINE 1: ...elompok\" (\"id_akun\", \"kode_kelompok\", \"kelompok\", \"tahun\") v...\n
        $origin=$message;
        $message=explode('\n',$message);
        $message=array_shift($message);

        // remove SQLSTATE[42703]:
        $message=explode(':',$message);
        array_shift($message);
        $message=implode(":",$message);
        $message=trim((string)$message);

        $message=str_replace('Undefined column','Definisi kolom index',$message);
        // hapus nama tabel
        $message=explode('of relation',$message);
        $message=array_shift($message);
        $message=trim((string)$message).' dalam entitas tabel tidak tersedia';
        $message.=$message[strlen($message)-1]!=='.'?'.':'';
        return $message;
    }

    /**
     * Decode error unknown function
     * @param string $message
     * @return string 
     */
    protected function trackPdoErrorFuncNotExists(string $message)
    {
        // SQLSTATE[42883]: Undefined function: 7 ERROR:  function _app_layanan_next_prosedure(integer) does not existLINE 1: SELECT _app_layanan_next_prosedure(4)               ^HINT:  No function matches the given name and argument types. You might need to add explicit type casts. (SQL: SELECT _app_layanan_next_prosedure(4))
        $origin=$message;
        $message=explode('\n',$message);
        $message=array_shift($message);
        $message=explode('ERROR',$message);
        array_shift($message);
        $message=array_shift($message);
        $message=ltrim($message,':');
        $message=trim((string)$message);
        $message=ltrim($message,'function');
        $message=trim((string)$message);

        $info=[
            rtrim($this->errorPdoDefs['42883'],'.'),
            '42883 : '.$message
        ];
        return implode('. ',$info).'.';
    }
}