<?php

use Illuminate\Database\Eloquent\Model;

function apiSuccess($data=[], $message='success !', $statusCode = 200)
{
    return response()->json(
        [
            'data' => $data,
            'errors' => [],
            'message' => $message,
            'statusCode' => $statusCode,
            'success' => true,
        ], $statusCode
    );
}
function apiError($errors = [], $message = 'something went wrong', $statusCode = 400) {
    return response()->json([
        'data'=>[],
        'errors' => $errors,
        'message' => $message,
        'statusCode' => $statusCode,
        'success' => false,
    ], $statusCode);
}

function apiFindOrFail($query,$id, $message = 'something went wrong') {
   
    try {

        $data = $query->findOrFail($id);
    } catch (\Throwable $th) {
        apiError(message: $message ,statusCode:404)->send();
        die();
    }
    return $data;
  

}