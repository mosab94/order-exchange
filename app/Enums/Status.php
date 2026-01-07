<?php

namespace App\Enums;

enum Status: int
{
    case Open = 1;
    case Filled = 2;
    case Cancelled = 3;
}
