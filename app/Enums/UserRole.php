<?php

namespace App\Enums;

enum UserRole: string
{
    case Visitor = 'visitor';
    case Member = 'member';
    case Admin = 'admin';
}
