<?php

class Response
{
    public static function RenderHtmlResponse($response)
    {
        echo $response;
    }

    public static function RenderJsonResponse($content)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($content);
    }
    public static function RenderJsonResponseAsync($content)
    {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        header("Connection: close");
        ignore_user_abort(true); // just to be safe
        ob_start();
        echo json_encode($content);
        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush(); // Strange behaviour, will not work
        flush(); // Unless both are called !
    }
    public static function RenderJsonError()
    {
        self::RenderJsonResponse(array(Key::KEY_ERROR => true));
        exit(0);
    }
}