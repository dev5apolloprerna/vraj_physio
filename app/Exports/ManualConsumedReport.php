<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ManualConsumedReport implements FromCollection, WithHeadings
{
    protected $rows;
    protected $fromDate;
    protected $toDate;
    protected $month;
    protected $year;

    public function __construct(array $rows, $fromDate = null, $toDate = null, $month = null, $year = null)
    {
        $this->rows = $rows;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return [
            ['Manually Consumed Session Report'],
            [
                'From Date: ' . ($this->fromDate ?? 'N/A'),
                'To Date: ' . ($this->toDate ?? 'N/A'),
                'Month: ' . ($this->month ? date('F', mktime(0, 0, 0, $this->month, 1)) : 'N/A'),
                'Year: ' . ($this->year ?? 'N/A'),
            ],
            [],
            ['Date', 'Patient Name', 'Treatment Name', 'Count'],
        ];
    }
}
