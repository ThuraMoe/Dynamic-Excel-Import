<?php

namespace App\Imports;

use App\Material;
use App\ClosingMaterial;
use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Validators\ValidationException;
use Mockery\Generator\StringManipulation\Pass\ClassPass;

class ClosingMaterialImport implements ToCollection, WithEvents
{
    use RegistersEventListeners;
    //, Importable;

    public static $fileHeaders;
    public static $constantHeader;
    public static $prepareData = [];

    public function __construct($headers)
    {
        // $this->fileHeaders = $headers;
        ClosingMaterialImport::$fileHeaders = $headers;
        // get constant header from database
        ClosingMaterialImport::$constantHeader = json_decode(config('ImportHeader'));
        ClosingMaterialImport::$prepareData = [];
    }

    /**
     * Check file header
     */
    public static function beforeImport(BeforeImport $event)
    {
        $worksheet = $event->reader->getActiveSheet();  
        
        // if user import file header is not equal from constants header from db
        if(ClosingMaterialImport::$constantHeader !== ClosingMaterialImport::$fileHeaders) {
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $errorMsg = trans('Excel file header format is invalid!');
            $failure = new Failure(1, 'rows', [0 => $errorMsg]);
            $failures = [0 => $failure];
            throw new ValidationException($error, $failures);
        } else {
            // prepare data to save into database
            $DBColumn = ClosingMaterialImport::$constantHeader;
            $DBColumnCount = count($DBColumn);
            $highestRow = $worksheet->getHighestRow(); // e.g. 10
            $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // e.g. 5

            // start loop from line 2 and prepare data to save
            for ($row = 2; $row <= $highestRow; ++$row) {
                $tmp = [];
                for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                    $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                    // highestColumnIndex and DBColumn count must be same
                    // As DBColumn is array and array is start from 0, subtract 1 from $col
                    $tmp[$DBColumn[$col - 1]] = $value;
                }
                ClosingMaterialImport::$prepareData[] = $tmp;
            }

            // get material code as array (DBColumn[0] must be material_code)
            $materialCodeArr = array_column(ClosingMaterialImport::$prepareData, $DBColumn[0]);
            $materialIdArr = Material::whereIn($DBColumn[0], $materialCodeArr)->select('id')->get();
            
            $cnt = count(ClosingMaterialImport::$prepareData);
            for($i=0; $i<$cnt; $i++) {
                // remove material_code and material name
                unset(ClosingMaterialImport::$prepareData[$i]['material_code']);
                unset(ClosingMaterialImport::$prepareData[$i]['material_name']);
                // add material_id into existing array
                ClosingMaterialImport::$prepareData[$i]['material_id'] = $materialIdArr[$i]->id;
                // add timestamp
                ClosingMaterialImport::$prepareData[$i]['created_at'] = now();
                ClosingMaterialImport::$prepareData[$i]['updated_at'] = now();
            }
        }
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        dump(ClosingMaterialImport::$prepareData);
        ClosingMaterial::insert(ClosingMaterialImport::$prepareData);
    }
}
