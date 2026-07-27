<?php

namespace WilliamBeeler\Crm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use League\Csv\Reader;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;

class ImportLeadsCommand extends Command
{
    protected $signature = 'leads:import {file : Path to the CSV file}';

    protected $description = 'Import leads from a CSV file into the companies table';

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
            $company->name = $record['Business Name'] ?? 'Unknown';
            $company->website = $record['Website'] ?? null;
            $company->email = $record['Email'] ?? null;
            $company->phone = $record['Phone'] ?? null;
            $company->poc_name = null;
            $company->poc_email = null;
            $company->poc_phone = null;
            $company->address_line1 = $record['Address'] ?? null;
            $company->address_line2 = null;
            $company->city = $record['City'] ?? null;
            $company->state = 'OK';
            $company->postal_code = null;
            $company->country = 'US';
            $company->source = $record['Source'] ?? null;
            $company->status_id = $defaultStatusId;

            // Anything else goes into meta
            // $meta = $record;
            // unset(
            //     $meta['name'], $meta['website'], $meta['email'], $meta['phone'],
            //     $meta['poc_name'], $meta['poc_email'], $meta['poc_phone'],
            //     $meta['address_line1'], $meta['address_line2'], $meta['city'],
            //     $meta['state'], $meta['postal_code'], $meta['country'],
            //     $meta['source'], $meta['status_id']
            // );
            $company->meta = null; // $meta;

            $company->save();
            $count++;
        }

        $this->info("✅ Successfully imported {$count} leads.");

        return Command::SUCCESS;
    }
}
