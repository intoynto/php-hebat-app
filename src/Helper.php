<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Psr\Http\Message\ServerRequestInterface as Request;

class Helper 
{
        /**
     * @param Request $request
     * @return string|null
     */
    public static function determineContentType($request)
    {
        $contents=[
            'application/json',
            'application/xml',
            'text/json',
            'text/xml',
            'text/html',
            'text/plain',
        ];

        $acceptHeader = $request->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(
            explode(',', $acceptHeader),
            $contents
        );

        $count = count($selectedContentTypes);
        if ($count) {
            $current = current($selectedContentTypes);

            /**
             * Ensure other supported content types take precedence over text/plain
             * when multiple content types are provided via Accept header.
             */
            if ($current === 'text/plain' && $count > 1) {
                $next = next($selectedContentTypes);
                if (is_string($next)) {
                    return $next;
                }
            }

            if (is_string($current)) {
                return $current;
            }
        }

        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            return $mediaType;
        }

        return null;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public static function determineContentJson($request)
    {
        $content_type=Helper::determineContentType($request);
        return in_array($content_type,['text/json','application/json']);
    }

    /**
     * Filter Sanitize File Name
     * @param string $filename
     * @return string
     */
    public static function sanitizeFileName($filename)
    {
        $filename=trim((string)$filename);
        // Replaces all spaces with hyphens.

        $filename = str_replace(' ', '-', $filename); 

        // Removes special chars. 
        $filename = preg_replace('/[^A-Za-z0-9\-\_]/', '', $filename); 

        // Replaces multiple hyphens with single one. 
        $filename = preg_replace('/-+/', '-', $filename); 

        return $filename;
    }

    /**
     * Load file and encode to base64
     * @param string $full_filename
     * @param string $data_mime default is 'data:image/png;base64,'
     * @return string|null
     */
    public static function loadFileToBase64($full_filename, $data_mime="data:image/png;base64,")
    {
        $img=null;
        if(file_exists($full_filename))
        {
            $img=base64_encode(file_get_contents($full_filename));
            if($data_mime) $img=$data_mime.$img;
        }
        return $img;
    }

    /**
     * Format String sesuai database 
     * Contoh 2020-12-31 
     * @param string $tanggal_awal format Y-m-d
     * @param string $tanggal_akhir format Y-m-d
     * @return int
     */
    public static function jumlahHari(string $tanggal_awal, string $tanggal_akhir)
    {
        $dari=strtotime($tanggal_awal);
        $sampai=strtotime($tanggal_akhir);

        $diff=$sampai - $dari;
        return round($diff / (60*60*24));
    }

    /**
     * Get nama bulan
     * @param int|string $bulan
     * @return string
     */
    public static function getNamaBulan($bulan)
    {
        $int=is_string($bulan)?(int)$bulan:$bulan;
        $nama='';
        if(is_numeric($int))
        {
            $int=$int<1?1:($int>12?12:$int);
            $bulans=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            $nama=$bulans[$int];
        }
        return $nama;
    }

    /**
     * Unzero int number
     * @param int|string $current
     * @param int $panjang
     * @return string
     */
    public static function unZero($current, $panjang=3)
    {
        $nomor=trim((string)$current);
        while(strlen($nomor)<$panjang)
        {
            $nomor="0".$nomor;
        }
        return $nomor;
    }

    /**
     * Proses rounding rupiah
     * @param float $float
     * @param int $pembulatan nilai minimum rupiah
     * @return int
     */
    public static function roundRupiah($float,$pembulatan=50)
    {
        if(is_string($float) || is_numeric($float))
        {
            $float=floatval($float);
        }
        else {
            $float=0;
        }

        $int=round($float);
        $sisa_bagi=$int % $pembulatan;
        if($sisa_bagi!==0)
        {
            $int+=$pembulatan - $sisa_bagi;
        }
        return (int)$int;
    }

    /**
     * Terbilang Indonesia
     * @param string|int|float $number
     * @return string
     */
    public static function terbilang($number)
    {
        $number = trim((string)str_replace('.', '', (string)$number)); 
        if(!is_numeric($number)) return "";
        
        $base    = array('nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan');
        $numeric = array('1000000000000000', '1000000000000', '1000000000000', 1000000000, 1000000, 1000, 100, 10, 1);
        $unit    = array('kuadriliun', 'triliun', 'biliun', 'milyar', 'juta', 'ribu', 'ratus', 'puluh', '');
        $str     = null;
        $i = 0;
        if ($number == 0) {
            $str = 'nol';
        } else {
            while ($number != 0) {
                $count = (int) ($number / $numeric[$i]);
                if ($count >= 10) {
                    $str .= static::terbilang($count) . ' ' . $unit[$i] . ' ';
                } elseif ($count > 0 && $count < 10) {
                    $str .= $base[$count] . ' ' . $unit[$i] . ' ';
                }
                $number -= $numeric[$i] * $count;
                $i++;
            }
            $str = preg_replace('/satu puluh (\w+)/i', '\1 belas', $str);
            $str = preg_replace('/satu (ribu|ratus|puluh|belas)/', 'se\1', $str);
            $str = preg_replace('/\s{2,}/', ' ', trim($str));
        }

        return ucfirst(trim($str));
    }
}