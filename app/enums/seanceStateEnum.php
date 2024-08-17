<?php
namespace App\Enums;
 enum seanceStateEnum: int
{
    case Cancel =0;
    case Defer =1;
    case Stop =2;
    case Done = 3;
}