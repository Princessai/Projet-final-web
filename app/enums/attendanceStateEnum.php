<?php

namespace App\Enums;

enum attendanceStateEnum: int
{
    case Absent = -1;
    case Late = 0;
    case Present = 1;
}
