<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;


class TreatmentExport implements FromCollection, WithHeadings,WithEvents
{
    protected $treatmentList;
    protected $fromDate;
    protected $toDate;
    protected $Month;
    protected $Year;

    public function __construct(array $treatmentList, $fromDate = null, $toDate = null,$Month= null ,$Year= null)
    {
        $this->treatmentList = $treatmentList;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->Month = $Month;
        $this->Year = $Year;
    }

    public function collection()
    {
        return collect($this->treatmentList);
    }
    public function headings(): array
    {
        return [
            ['Total Session Report'], // Title Row
            ['From Date: ' . ($this->fromDate ?? 'N/A'), 'To Date: ' . ($this->toDate ?? 'N/A'), 'Month: ' . ($this->Month ?? 'N/A'), 'Year: ' . ($this->Year ?? 'N/A')], // Date Row
            [], // Empty row
            ['Treatment Name', 'Attended Session', 'Amount Per Session', 'Total'], // Table Headers
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Get last row number dynamically
                $lastRow = count($this->treatmentList) + 4; // 4 rows for headings
                
                // Calculate totals
                $totalSessions = array_sum(array_column($this->treatmentList, 'attended_session'));
                $totalAmountPerSession = array_sum(array_column($this->treatmentList, 'amount'));
                $grandTotal = array_sum(array_column($this->treatmentList, 'total'));

                // Add total row
                $sheet->setCellValue('A' . ($lastRow + 1), 'Total');
                $sheet->setCellValue('B' . ($lastRow + 1), $totalSessions);
                $sheet->setCellValue('C' . ($lastRow + 1), $totalAmountPerSession);
                $sheet->setCellValue('D' . ($lastRow + 1), $grandTotal);

                // Apply bold style to total row
                $sheet->getStyle('A' . ($lastRow + 1) . ':D' . ($lastRow + 1))->getFont()->setBold(true);
            },
        ];
    }
    
}
?>