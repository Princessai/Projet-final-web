<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NoWorkedHoursException extends Exception
{
       /**
     * Report the exception.
     */
    public function report(): void
    {
        // ...
    }
 
    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request)

    {
        if ($request->is('api/*')) {

            return  apiError(message: 'no courses has been done for this interval');
        }

        return false;

    }
}
