<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class GroupSessionExport implements FromCollection, WithHeadings
{
    protected $groupsessionList;
    protected $patient_id;
    protected $fromDate;
    protected $toDate;
    protected $Month;
    protected $Year;

    public function __construct($groupsessionList, $patient_id=null,$fromDate = null, $toDate = null,$Month= null ,$Year= null)
    {
        $this->groupSession = $groupsessionList;
        $this->patient_id = $patient_id;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->Month = $Month;
        $this->Year = $Year;

    }
    
    public function collection()
    {
        return collect($this->groupSession);
    }
     public function headings(): array
    {
        return [
            ['Group Session Report'], // Title Row (Bold)
            ['patient id: ' . ($this->patient_id ?? 'N/A'),'From Date: ' . ($this->fromDate ?? 'N/A'), 'To Date: ' . ($this->toDate ?? 'N/A'), 'Month: ' . ($this->Month ?? 'N/A'), 'Year: ' . ($this->Year ?? 'N/A')], // Date Row
            [], // Empty row for spacing
            ['Patient Name',
            'Date',
            'Start Time', // Actual column headings
            'End Time',
            'Therapist Name',
            'Treatment Name',
            'Group Session',
            ] // Actual column headings
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
?>