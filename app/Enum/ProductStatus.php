<?php

namespace App\Enum;

enum ProductStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}
