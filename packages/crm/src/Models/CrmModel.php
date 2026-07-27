<?php

namespace WilliamBeeler\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use WilliamBeeler\Crm\Support\TableNames;

abstract class CrmModel extends Model
{
    protected string $crmTable;

    public function getTable(): string
    {
        return TableNames::get($this->crmTable);
    }
}
