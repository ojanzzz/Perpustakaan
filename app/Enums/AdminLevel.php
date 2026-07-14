<?php

namespace App\Enums;

enum AdminLevel: string
{
    case Editor = 'editor';
    case ContentAdmin = 'content_admin';
    case Auditor = 'auditor';
    case Superadmin = 'superadmin';
}
