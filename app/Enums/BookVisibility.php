<?php

namespace App\Enums;

enum BookVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';
    case Password = 'password';
    case VerifiedEmail = 'verified_email';
    case Role = 'role';
    case Scheduled = 'scheduled';
    case Expiring = 'expiring';
}
