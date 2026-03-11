<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin   = 'ADMIN';
    case Manager = 'MANAGER';
    case Finance = 'FINANCE';
    case User    = 'USER';
}
