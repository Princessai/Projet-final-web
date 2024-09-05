<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

function apiFindOrFail($query,$id, $message = 'something went wrong',string|array|null $attributes =null) {
   
    try {
        if($attributes!==null){
            if(is_array($attributes)){
                $query->select(...$attributes);
            }
            $query->select($attributes);
          
        }
        $data = $query->findOrFail($id);
    } catch (ModelNotFoundException $th) {
        throw new HttpResponseException( apiError(message: $message ,statusCode:404));

    }
    return $data;
  

}