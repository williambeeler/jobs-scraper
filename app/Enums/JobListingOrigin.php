<?php

namespace App\Enums;

enum JobListingOrigin: string
{
    case Scraped = 'scraped';
    case Custom = 'custom';
}
