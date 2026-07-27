<?php

namespace WilliamBeeler\Crm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use League\Csv\Reader;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;

class ImportSBCLeadsCommand extends Command
{
    protected $signature = 'leads:import-sbc {file : Path to the CSV file}';

    protected $description = 'Import SBC leads from a CSV file into the companies table';

    public function handle()
    {
        $defaultStatusId = Status::query()->where('slug', 'new')->value('id');
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        $count = 0;
        foreach ($records as $record) {
            // Adjust these field names based on your CSV headers
            $company = new Company;
            $company->uuid = Str::uuid();
            $company->name = $record['company_name'] ?? 'Unknown';
            $company->website = null;
            $company->email = $record['email'] ?? null;
            $company->phone = $record['phone'] ?? null;
            $company->poc_name = null;
            $company->poc_email = null;
            $company->poc_phone = null;
            $company->address_line1 = $record['street'] ?? null;
            $company->address_line2 = null;
            $company->city = $record['city'] ?? null;
            $company->state = $record['state'] ?? null;
            $company->postal_code = $record['postal_code'] ?? null;
            $company->country = $record['country'] ?? null;
            $company->source = $record['Source'] ?? null;
            $company->status_id = $defaultStatusId;

            $company->meta = null; // $meta;

            $company->save();
            $count++;
        }

        $this->info("✅ Successfully imported {$count} leads.");

        return Command::SUCCESS;
    }
}
