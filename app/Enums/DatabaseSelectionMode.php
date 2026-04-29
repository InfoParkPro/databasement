<?php

namespace App\Enums;

enum DatabaseSelectionMode: string
{
    case All = 'all';
    case Selected = 'selected';
    case Pattern = 'pattern';
}
