<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('x_cors_helper'))
{
    function x_cors_helper()
    {
        //log_message('debug', 'xie CORS-Header gerufen: ');

        // muss man so machen, mann kann nur einen mitschicken
        $allowedOrigins = [
            'http://localhost:9001',
            'https://localhost:9001',
            'https://maricare.marishine.de',
            'https://m.marishine.de',
            'https://app.marishine.de'
        ];

        // only post not get !!!
        if ( ( isset($_SERVER['HTTP_ORIGIN']) || isset($_SERVER['POST']) )
            && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins) ) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Credentials: true');
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
            header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization');
        }
     
        //log_message('debug', 'xie CORS-Header wurden gesetzt: ' . json_encode(headers_list()));

        // Preflight Anfragen direkt beantworten:
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit();
        }
        //log_message('debug', 'xie CORS-Header OHNE option: ' . json_encode(headers_list()));
    }
}
