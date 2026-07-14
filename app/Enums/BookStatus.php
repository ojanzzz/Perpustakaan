<?php

namespace App\Enums;

enum BookStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Private = 'private';
    case Archived = 'archived';
}
