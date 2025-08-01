<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Color;

class FacturasExport implements FromArray, WithStyles, WithTitle
{
    protected $data;
    protected $headers;
    protected $sheetTitle;

    public function __construct(array $headers, array $data, string $title = 'Factura XML')
    {
        $this->headers = $headers;
        $this->data = $data;
        $this->sheetTitle = $title;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->headers);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $highestRow = count($this->data);

        $headerRange = 'A1:' . $lastColumn . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE]
            ],
            'alignment' => [
                'horizontal' => 'center',
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1F4E78'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $dataRange = 'A2:' . $lastColumn . $highestRow;
        $sheet->getStyle($dataRange)->applyFromArray([
            'alignment' => [
                'horizontal' => 'center',
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        for ($i = 1; $i <= $columnCount; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(40);
            $sheet->getDefaultRowDimension()->setRowHeight(18);
        }

        return [];
    }
}
