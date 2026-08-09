<?php

namespace App\Exports\Administrasi;

use App\Models\Administrasi\ProjectQuotation;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProjectQuotationMultiExport implements WithMultipleSheets
{
    protected $quotationNumbers;
    protected $exportClass;

    public function __construct(array $quotationNumbers, string $exportClass = ProjectQuotationExport::class)
    {
        $this->quotationNumbers = $quotationNumbers;
        $this->exportClass = $exportClass;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->quotationNumbers as $number) {
            $sheets[] = new $this->exportClass($number);
        }

        return $sheets;
    }
}
