<?php

namespace App\Services;



class AbsenceService
{


    public function ReceiptDir()
    {
        $dirName = "receipts";
        $dirPath = storage_path("app/public/$dirName");
        return ["dirName" => $dirName, "dirPath" => $dirPath];
    }
    public function ReceiptDirThumb()
    {
        $thumbDirName = "receiptsThumb";
        $thumbDirPath = storage_path("app/public/$thumbDirName");
        return ["dirName" => $thumbDirName, "dirPath" => $thumbDirPath];
    }
}
