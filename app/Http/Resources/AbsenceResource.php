<?php

namespace App\Http\Resources;

use App\Services\AbsenceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsenceResource extends JsonResource
{


    /**
     * Indicates if the resource's collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = true;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $AbsenceService = new AbsenceService;
        $receiptThumb = null;
        $receiptImage = null;
        $receiptFile = null;

        if ($this->receipt != null) {
            $fileExtension = explode('.', $this->receipt)[1];
            ['dirName' => $receiptDirName] = $AbsenceService->ReceiptDir();
            ['dirName' => $thumbDirName] = $AbsenceService->ReceiptDirThumb();
            $receiptFile = asset("storage/$receiptDirName/$this->receipt");
            if (strtolower($fileExtension) !== 'pdf') {
                $receiptThumb = asset("storage/$thumbDirName/$this->receipt");
                $receiptImage = $receiptFile;
            }
        }


        return  [


            "id" => $this->id,
            "etat" => $this->etat,
            "type_seance" => $this->when($this->relationLoaded('seance')
                && $this->seance->relationLoaded('typeSeance'), $this->seance->typeSeance->label),
            "module" => $this->whenLoaded('module', function () {
                return $this->module->label;
            }),
            "coordinateur_id" => $this->coordinateur_id,
            "seance_heure_debut" => $this->seance_heure_debut,
            "seance_heure_fin" => $this->seance_heure_fin,
            "created_at" => $this->created_at,
            "annee_id" => $this->annee_id,
            'receipt' => $this->receipt,
            'comments' => $this->comments,
            'receiptFile' => $receiptFile,
            'receiptThumb' => $receiptThumb,
            'receiptImage' => $receiptImage
            

        ];
    }
}
