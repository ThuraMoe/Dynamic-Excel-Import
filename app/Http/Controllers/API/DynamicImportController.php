<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportFileValidation;
use App\Imports\ClosingMaterialImport;
use Illuminate\Http\Request;
// use Maatwebsite\Excel\Facades\Excel;
use Excel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\HeadingRowImport;

class DynamicImportController extends Controller
{
    public function import(ImportFileValidation $request) 
    {
        try {
            // get heading from import file
            $headings = (new HeadingRowImport())->toArray($request->file('import_file'));
            // get only first sheet header 
            $headings = $headings[0][0];
            Excel::import(new ClosingMaterialImport($headings), $request->file('import_file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) { 
            Log::channel('debuglog')->debug($e->getMessage());                  
            $failures = $e->failures();
            $errorMsg = [];
            //if error occur
            foreach ($failures as $failure) {
                $errorMsg[] = 'Row '.$failure->row().'=> '.implode(',',$failure->errors());
            }
            return response()->json(['status'=>'NG', 'message'=>$errorMsg], 200);
        }
    }
}
