<?php

namespace App\Http\Controllers;

// This is a base controller class that other controllers can extend.
abstract class Controller
{
    /**
     * Return a successful response with the provided data.
     *
     * @param mixed $data The data to be included in the success response.
     * @return \Illuminate\Http\Response The HTTP response object.
     */
             public function success($data): \Illuminate\Http\Response{
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Return an error response.
     *
     * @param string $message The error message to return
     * @param int $code The HTTP status code (default: 400)
     * @return \Illuminate\Http\Response
     */
    public function error($message, $code = 400): \Illuminate\Http\Response{
        
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $code);
    }
}
