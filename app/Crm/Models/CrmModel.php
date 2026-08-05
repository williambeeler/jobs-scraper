<?php

namespace App\Crm\Models;

use App\Crm\Support\TableNames;
use Illuminate\Database\Eloquent\Model;

abstract class CrmModel extends Model
{
    protected string $crmTable;

    public function getTable(): string
    {
        return TableNames::get($this->crmTable);
    }
}
