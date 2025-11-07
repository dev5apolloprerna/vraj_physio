<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PatientVisit implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $data;
    protected $treatmentNames;

    /*public function __construct(array $data)
    {
        $this->data = $data;

        // Dynamically get all treatment names used
        $allTreatments = [];
        foreach ($data as $row) {
            if (isset($row['treatments']) && is_array($row['treatments'])) {
                $allTreatments = array_merge($allTreatments, array_keys($row['treatments']));
            }
        }

        $this->treatmentNames = array_values(array_unique($allTreatments));
    }*/

public function __construct(array $data)
{
    $this->data = $data;

    $treatmentCounts = [];

    foreach ($data as $row) {
        if (isset($row['treatments']) && is_array($row['treatments'])) {
            foreach ($row['treatments'] as $treatment => $count) {
                if ((int) $count > 0) {
                    $treatmentCounts[$treatment] = true;
                }
            }
        }
    }

    // Only keep treatments that have at least one non-zero count
    $this->treatmentNames = array_keys($treatmentCounts);
}

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return array_merge(['PATIENT NAME'], $this->treatmentNames, ['TOTAL']);
    }

    public function map($row): array
{
    $mapped = [$row['patient_name']];
    $total = 0;

    foreach ($this->treatmentNames as $treatment) {
        if (isset($row['treatments'][$treatment])) {
            $value = (int) $row['treatments'][$treatment];
            $mapped[] = $value > 0 ? $value : '-';
            $total += $value;
        } else {
            $mapped[] = '-';
        }
    }

    $mapped[] = $total;

    return $mapped;
}


    public function styles(Worksheet $sheet)
    {
        $lastColumn = chr(65 + count($this->treatmentNames) + 1); // Adjust based on column count
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);

        // Center all cells
        $sheet->getStyle('A1:' . $lastColumn . ($sheet->getHighestRow()))->getAlignment()->setHorizontal('center');

        return [];
    }
}
