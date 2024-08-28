<?php

namespace App\Enums;

enum crudActionEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
