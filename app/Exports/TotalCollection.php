<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class TotalCollection implements FromCollection, WithHeadings,WithEvents
{
    protected $collectionList;
    protected $fromDate;
    protected $toDate;
    protected $Month;
    protected $Year;


    public function __construct(array $pList, $fromDate = null, $toDate = null,$Month= null ,$Year= null)
    {
        $this->collectionList = $pList;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->Month = $Month;
        $this->Year = $Year;

    }

    public function collection()
    {
        return collect($this->collectionList);
        
    }

    public function headings(): array
    {
        return [
            ['Total Collection Report'], // Title Row (Bold)
            ['From Date: ' . ($this->fromDate ?? 'N/A'), 'To Date: ' . ($this->toDate ?? 'N/A'), 'Month: ' . ($this->Month ?? 'N/A'), 'Year: ' . ($this->Year ?? 'N/A')], // Date Row
            [], // Empty row for spacing
            ['Payment Date',
            'Online',
            'Cash',
            'NEFT',
            'Card',
            'Total Amount',] // Actual column headings
        ];
    }
      public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Get last row number dynamically
                $lastRow = count($this->collectionList) + 6; // 6 rows for headings
                
                // Calculate totals
                $totalonline = array_sum(array_column($this->collectionList, 'Online'));
                $totalcash = array_sum(array_column($this->collectionList, 'Cash'));
                $totalNEFT = array_sum(array_column($this->collectionList, 'NEFT'));
                $totalCard = array_sum(array_column($this->collectionList, 'Card'));
                $grandTotal = array_sum(array_column($this->collectionList, 'Total_Amount'));

                // Add total row
                $sheet->setCellValue('A' . ($lastRow + 1), 'Total');
                $sheet->setCellValue('B' . ($lastRow + 1), $totalonline);
                $sheet->setCellValue('C' . ($lastRow + 1), $totalcash);
                $sheet->setCellValue('D' . ($lastRow + 1), $totalNEFT);
                $sheet->setCellValue('E' . ($lastRow + 1), $totalCard);
                $sheet->setCellValue('F' . ($lastRow + 1), $grandTotal);

                // Apply bold style to total row
                $sheet->getStyle('A' . ($lastRow + 1) . ':F' . ($lastRow + 1))->getFont()->setBold(true);
            },
        ];
    }
    
}
