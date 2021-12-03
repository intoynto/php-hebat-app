<?php

namespace Intoy\HebatApp\Twig;

use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Intoy\HebatFactory\Foundation\Guard;

class TwigHelperExtension extends AbstractExtension
{

    public function getName()
    {
        return 'hebat.twig.helper.extension';
    }

    public function getAsset(string $path="")
    {
        return url_asset($path);
    }

    public function getFullAsset(string $path="")
    {
        return full_url_asset($path);
    }


    /**
     * @param $value string
     * @return \DateTime|null
     */
    public static function toDateTime($value)
    {
        if($value instanceof DateTime) return $value;

        if(!is_string($value)) return;

        $format='Y-m-d';
        $newValue=date_create_from_format($format,$value);
        $true=$newValue!==false;
        if($true) return $newValue;

        //check date Y-m-d h:i:s split by space
        $newValue=date_create_from_format($format, explode(" ",$value)[0]);
        $true=$newValue!==false;
        return $true?$newValue:null;
    }

    public static function getNamaHari($value)
    {
        $newValue=static::toDateTime($value);        
        if($newValue)
        {
            $hari=$newValue->format('D');
            $hari=strtolower(trim((string)$hari));
            $namaHaris=[
                'sun'=>'Minggu',
                'mon'=>'Senin',
                'tue'=>'Selasa',
                'wed'=>'Rabu',
                'thu'=>'Kamis',
                'fri'=>'Jumat',
                'sat'=>'Sabtu',
            ];
            return in_array($hari,array_keys($namaHaris))?$namaHaris[$hari]:null;
        }
        return null;
    }

    public static function getNamaBulan($value, bool $long=true)
    {
        $date=static::toDateTime($value);
        if($date)
        {
            $m=(int)$date->format('m'); // 01 - 12;
            $bulans=[
                "",//empty 0
                "Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember",
            ];
            $bulan=isset($bulans[$m])?$bulans[$m]:null;
            if($bulan)
            {
                return $long?$bulan:substr($bulan,0,3);
            }
        }
        return null;
    }


    /**
     * @param string
     */
    public function getFormatTanggal($value,string $separator=" ",bool $long=true)
    {
        $date=static::toDateTime($value);
        if(!$date) return null;

        $tanggal=$date->format("d"); // 01 - 31
        $bulan=$long?static::getNamaBulan($date,$long):$date->format("m");
        $tahun=$date->format('Y');
        $formats=[$tanggal,$bulan,$tahun];
        return implode($separator,$formats);
    }


    public function generateGuard()
    {
        $guard=app()->resolve(Guard::class); // get csrf

        $guard->generateToken(); //generate new token
        $nameField= $guard->getTokenNameKey();
        $nameValue= $guard->getTokenName();

        $valueField= $guard->getTokenValueKey();        
        $valueValue= $guard->getTokenValue(); 

        $csrf=[
            'field'=>'
                <input type="hidden" name="'.$nameField.'" value="'.$nameValue.'" />
                <input type="hidden" name="'.$valueField.'" value="'.$valueValue.'" />
            '
        ];
        return (object)$csrf;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('asset',[$this,'getAsset']),
            new TwigFunction('full_asset',[$this,'getFullAsset']),
            new TwigFunction('nama_hari',[$this,'getNamaHari']),
            new TwigFunction('format_tanggal',[$this,'getFormatTanggal']),
            new TwigFunction('guard',[$this,'generateGuard']),
        ];
    }    
}