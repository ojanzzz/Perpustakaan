<?php

namespace App\Enums;

enum UserRole: string
{
    case Public = 'public';
    case Member = 'member';
    case Superadmin = 'superadmin';
}
