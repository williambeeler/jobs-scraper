<?php

namespace App\Data;

final readonly class JobListingData
{
    public function __construct(
        public string $source,
        public string $title,
        public string $company,
        public string $location,
        public string $url,
        public ?string $postedDate = null,
        public ?string $postedRelative = null,
        public ?string $salary = null,
    ) {}
}
