<?php
declare (strict_types=1);

namespace Intoy\HebatApp;

use Psr\Http\Message\ServerRequestInterface as Request;

class Helper 
{
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
     * @return string|null
     */
    public static function determineContentType(Request $request)
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
}