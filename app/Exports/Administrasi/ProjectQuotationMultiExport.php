<?php

namespace App\Exports\Administrasi;

use App\Models\Administrasi\ProjectQuotation;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProjectQuotationMultiExport implements WithMultipleSheets
{
    protected $quotationNumbers;

    public function __construct(array $quotationNumbers)
    {
        $this->quotationNumbers = $quotationNumbers;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->quotationNumbers as $number) {
            $sheets[] = new ProjectQuotationExport($number);
        }

        return $sheets;
    }
}
