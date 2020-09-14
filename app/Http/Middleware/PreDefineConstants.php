<?php

namespace App\Http\Middleware;

use App\Constant;
use Closure;

class PreDefineConstants
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Fetch all constanat table data
        $dbConstantValue = Constant::all();
        
        // If constant table data is not empty, loop the db data and split kye and value
        if(!$dbConstantValue->isEmpty()){
            foreach($dbConstantValue as $const){               
                config([$const->key => $const->value]);
            }
        }      
        return $next($request);
    }
}
