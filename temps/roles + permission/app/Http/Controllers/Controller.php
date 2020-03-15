<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function checkPermission($permission){
        if(Auth::user()->hasPermissionTo($permission) || Auth::user()->hasPermissionTo('Super')):
            return true;
        else:
            abort('401');
        endif;
    }


}
