<?php

function apiSuccess($data='', $message='success !', $statusCode = 200)
{
    return response()->json(
        [
            'data' => $data,
            'message' => $message,
            'statusCode' => $statusCode,
            'success' => true,
        ], $statusCode
    );
}
function apiError($errors = [], $message = 'something went wrong', $statusCode = 400) {
    return response()->json([
        'errors' => $errors,
        'message' => $message,
        'statusCode' => $statusCode,
        'success' => false,
    ], $statusCode);
}
