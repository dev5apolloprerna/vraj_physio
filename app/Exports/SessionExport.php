<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SessionExport implements FromCollection, WithHeadings
{
    protected $sessionList;
    protected $fromDate;
    protected $toDate;
    protected $Month;
    protected $Year;

    public function __construct(array $sessionList, $fromDate = null, $toDate = null,$Month= null ,$Year= null)
    {
        $this->sessionList = $sessionList;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->Month = $Month;
        $this->Year = $Year;

        
    }

    public function collection()
    {
        return collect($this->sessionList);
    }

    public function headings(): array
    {
        return [
            ['Patient Attended Session Report'], // Title Row (Bold)
            ['From Date: ' . ($this->fromDate ?? 'N/A'), 'To Date: ' . ($this->toDate ?? 'N/A'), 'Month: ' . ($this->Month ? date('F', mktime(0, 0, 0, $this->Month, 1)) : 'N/A'), 'Year: ' . ($this->Year ?? 'N/A')], // Date Row
            [], // Empty row for spacing
            ['Invoice No',
            'Date',
            'Patient Name',
            'Therapist Name',
            'Treatment Name',
            'Group Session',
            'Per Session Amount'] // Actual column headings
        ];
    }
            public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Merge Title Row and Apply Bold Styling
                $sheet->mergeCells('A1:F1'); // Merge across 6 columns
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                // Make 'From Date' and 'To Date' Bold
                $sheet->getStyle('A2:B2')->getFont()->setBold(true);
                
                // Make Column Headings Bold
                $sheet->getStyle('A4:F4')->getFont()->setBold(true);
            }
        ];
    }

}
