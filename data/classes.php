<?php

$niveaux = require(base_path('data/niveaux.php'));
$filieres = require(base_path('data/filieres.php'));




// return  [
// "Prepa 1",
// "Prepa 2",
// "Prepa communication",
// "Prepa developpement",
// "Prepa création",
// "B2 communication",
// "B2 developpement",
// "B2 création",
// "B3 communication",
// "B3 developpement",
// "B3 création",

// ];

$specificCombinationArr = [
    ["niveau"=>$niveaux["prepa"],"filiere"=>$filieres["mixte"], "alias"=>"Prepa","restricted"=>true,"classCount"=>2],

];

$classes=[];
foreach ($niveaux as  $niveau) {

  
    foreach ($filieres as $section) {
       


            $specificCombinationSection = array_filter($specificCombinationArr, function($element) use ($niveau,$section) {

            return $element['filiere']['label'] === $section['label'];
        });
        if(!empty($specificCombinationSection)){

            $specificCombinationSection=$specificCombinationSection[0];

            $isRestrictedSection=isset($specificCombinationSection['restricted'])?$specificCombinationSection['restricted']:false;
            

        }

    
        if(!empty($specificCombinationSection)&&$isRestrictedSection&&$specificCombinationSection["niveau"]["label"]==$niveau['label']){
            $combinationAlias=isset($specificCombinationSection["alias"])?$specificCombinationSection["alias"]:"{$niveau['alias']} {$section['label']}";

            if(isset($specificCombinationSection["classCount"])){


                $classCount = (int) $specificCombinationSection["classCount"];
                
                for($classeCounter=1;$classeCounter<=$classCount;$classeCounter++){
                    
                    $classes[]= [ "label"=>$combinationAlias.' '.$classeCounter, "section"=>$section, "level"=>$niveau ]    ;
                }
            }else{
                $classes[]=[ "label"=>$combinationAlias, "section"=>$section, "level"=>$niveau ];
            }
        };

        if(empty($specificCombinationSection)||empty($specificCombinationSection)&&!$isRestrictedSection&&$specificCombinationSection["niveau"]["label"]!=$niveau['label']  ){
            $classeName=" {$niveau['alias']} {$section['label']}";
            $classes[]= [ "label"=>$classeName, "section"=>$section, "level"=>$niveau ];
        };

    }

    
}

return $classes ;