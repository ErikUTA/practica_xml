<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class XMLController extends Controller
{
    public function convertirFacturaXmlACsv(Request $request)
    {
        $request->validate([
            'xml_files' => 'required|array',
            'xml_files.*' => 'file|mimes:xml',
        ]);

        $csvPath = storage_path('app/private/facturas/factura_excel.csv');
        file_put_contents($csvPath, "\xEF\xBB\xBF");
        $handle = fopen($csvPath, 'a');

        foreach ($request->file('xml_files') as $xmlFile) {
            $xml = simplexml_load_file($xmlFile->getRealPath());
            if (!$xml) {
                continue;
            }

            $dataRows = [];
            $headers = [];

            $this->extractXmlData($xml, $dataRows, $headers);

            if (empty($dataRows)) {
                continue;
            }

            fputcsv($handle, []); // línea en blanco
            fputcsv($handle, ['Archivo: ' . $xmlFile->getClientOriginalName()]);
            fputcsv($handle, $headers);

            foreach ($dataRows as $row) {
                $csvRow = [];
                foreach ($headers as $header) {
                    $csvRow[] = $row[$header] ?? '';
                }
                fputcsv($handle, $csvRow);
            }

            fputcsv($handle, []); // línea en blanco
        }

        fclose($handle);

        return response()->download($csvPath, 'factura_excel.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function extractXmlData($element, &$rows, &$headers, $path = '')
    {
        $row = [];
        $fullPath = $path . ($path ? '.' : '') . $element->getName();

        // Atributos del nodo actual
        foreach ($element->attributes() as $attrName => $attrValue) {
            $key = $fullPath . '@' . $attrName;
            $row[$key] = (string) $attrValue;

            if (!in_array($key, $headers)) {
                $headers[] = $key;
            }
        }

        // Si el nodo tiene texto y no solo hijos o atributos
        $text = trim((string) $element);
        if ($text !== '' && count($element->children()) === 0) {
            $key = $fullPath . '#text';
            $row[$key] = $text;
            if (!in_array($key, $headers)) {
                $headers[] = $key;
            }
        }

        // Añadir esta fila si hay al menos un valor
        if (!empty($row)) {
            $rows[] = $row;
        }

        // Repetir para hijos
        foreach ($element->children() as $child) {
            $this->extractXmlData($child, $rows, $headers, $fullPath);
        }
    }

    public function hashPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $hashed = Hash::make($request->password);

        return response()->json([
            'hashed_password' => $hashed,
        ]);
    }
}
