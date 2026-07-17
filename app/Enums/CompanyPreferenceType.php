<?php

namespace App\Enums;

enum CompanyPreferenceType: string
{
    case Whitelist = 'whitelist';
    case Blacklist = 'blacklist';
}
