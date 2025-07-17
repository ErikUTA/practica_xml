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
        $lastColumn  = Coordinate::stringFromColumnIndex($columnCount);
        $highestRow  = $sheet->getHighestRow();

        $titles = [
            'Comprobante',
            'Emisor',
            'Receptor',
            'Conceptos',
            'Concepto Traslado',
            'Impuestos',
            'Impuesto Traslados',
            'Complemento Timbre Fiscal Digital',
        ];

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($colIndex = 1; $colIndex <= $columnCount; $colIndex++) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $cellCoord = $colLetter . $row;
                $cellValue = $sheet->getCell($cellCoord)->getValue();

                if (in_array((string)$cellValue, $this->headers, true)) {
                    $sheet->getStyle($cellCoord)->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => 'center'],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE6F0FA'],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);
                } elseif (in_array((string)$cellValue, $titles, true)) {
                    $span = match ((string)$cellValue) {
                        'Comprobante' => 14,
                        'Emisor' => 3,
                        'Receptor' => 3,
                        'Conceptos' => 4,
                        'Concepto Traslado' => 5,
                        'Impuestos' => 1,
                        'Impuesto Traslados' => 4,
                        'Complemento Timbre Fiscal Digital' => 7,
                        default => 1,
                    };

                    $startColLetter = $colLetter;
                    $endColLetter = Coordinate::stringFromColumnIndex($colIndex + $span - 1);
                    $mergeRange = $startColLetter . $row . ':' . $endColLetter . $row;

                    $sheet->mergeCells($mergeRange);

                    $sheet->getStyle($mergeRange)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['argb' => Color::COLOR_WHITE]
                        ],
                        'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
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
                } elseif (!is_null($cellValue) && trim((string)$cellValue) !== '') {
                    $sheet->getStyle($cellCoord)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFFFFFFF'],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);
                }
            }
        }

        $dataRange = 'A2:' . $lastColumn . (count($this->data) + 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
                'wrapText' => false,
            ],
        ]);

        $highestColumn = $sheet->getHighestColumn();
        $columnCount = Coordinate::columnIndexFromString($highestColumn);

        foreach (range(1, $columnCount) as $index) {
            $col = Coordinate::stringFromColumnIndex($index);
            $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(30);
        }

        return [];
    }
}