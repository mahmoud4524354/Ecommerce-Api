<?php

namespace App\Helpers;

class ApiResponse
{
    static function sendResponse($data, $message, $code = 200)
    {
        $response = [
            'status' => $code,
            'msg' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }

    public static function sendError($msg = 'Error', $code = 400)
    {
        $response = [
            'status' => $code,
            'msg' => $msg,
            'data' => null,
        ];

        return response()->json($response, $code);
    }
}
