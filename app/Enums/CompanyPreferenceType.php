<?php

namespace App\Enums;

enum CompanyPreferenceType: string
{
    case Whitelist = 'whitelist';
    case Verified = 'verified';
    case Blacklist = 'blacklist';
}
