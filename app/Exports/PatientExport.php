<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class PatientExport implements FromCollection, WithHeadings
{
    protected $plist;
    protected $Search;
    
    public function __construct($plist, $search = null)
    {
        $this->collectionList = $plist;
        $this->Search = $search;

    }
    
    public function collection()
    {
        return collect($this->collectionList);
    }

        public function headings(): array
    {
        return [
            ['Patient List'], // Title Row (Bold)
            ['Patient Name: ' . ($this->Search ?? 'N/A')], // Date Row
            [], // Empty row for spacing
            ['Patient Name',
            'Patient Case No' ,
            'Mobile' ,
            'Other Mobile' ,
            'Email',
            'Age',
            'DOB'
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