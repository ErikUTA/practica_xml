<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FacturasExport;
use Illuminate\Support\Facades\Storage;

class XMLController extends Controller
{
    public function convertirFacturaXmlACsv(Request $request)
    {
        $request->validate([
            'xml_files' => 'required|array',
            'xml_files.*' => 'file|mimes:xml',
        ]);

        $headers = [
            'Certificado', 'Fecha', 'Folio', 'FormaPago', 'LugarExpedicion',
            'MetodoPago', 'Moneda', 'NoCertificado', 'Sello', 'Serie',
            'SubTotal', 'TipoDeComprobante', 'Total', 'Version',
            'Nombre', 'RegimenFiscal', 'Rfc', 'Nombre', 'Rfc', 'UsoCFDI',
            'Cantidad', 'ClaveProdServ', 'ClaveUnidad', 'Descripcion',
            'Base', 'Importe', 'Impuesto', 'TasaOCuota', 'TipoFactor',
            'TotalImpuestosTrasladados', 'Importe', 'Impuesto', 'TasaOCuota', 'TipoFactor',
            'FechaTimbrado', 'NoCertificadoSAT', 'RfcProvCertif', 'SelloCFD', 'SelloSAT', 'UUID', 'Version',
        ];

        $headers = array_unique($headers);

        $allRows = [];

        foreach ($request->file('xml_files') as $file) {
            $xml = simplexml_load_file($file->getRealPath());
            if (!$xml) continue;

            $namespaces = $xml->getNamespaces(true);
            $cfdi = $xml->children($namespaces['cfdi']);
            $emisor = $cfdi->Emisor ?? null;
            $receptor = $cfdi->Receptor ?? null;
            $impuestos = $cfdi->Impuestos ?? null;
            $complemento = $cfdi->Complemento ?? null;
            $conceptos = $cfdi->Conceptos ?? null;

            $rows = [];

            // Sección: Comprobante
            $rows[] = array_fill(0, 1, '');
            $rows[] = ['Archivo: ' . $file->getClientOriginalName()];
            $rows[] = array_fill(0, 1, '');
            $rows[] = ['Comprobante'];
            $rows[] = [
                'Certificado', 'Fecha', 'Folio', 'FormaPago', 'LugarExpedicion',
                'MetodoPago', 'Moneda', 'NoCertificado', 'Sello', 'Serie',
                'SubTotal', 'TipoDeComprobante', 'Total', 'Version'
            ];
            $rows[] = [
                (string) $xml['Certificado'], (string) $xml['Fecha'], (string) $xml['Folio'],
                (string) $xml['FormaPago'], (string) $xml['LugarExpedicion'], (string) $xml['MetodoPago'],
                (string) $xml['Moneda'], (string) $xml['NoCertificado'], (string) $xml['Sello'],
                (string) $xml['Serie'], (string) $xml['SubTotal'], (string) $xml['TipoDeComprobante'],
                (string) $xml['Total'], (string) $xml['Version']
            ];

            // Sección: Emisor
            $rows[] = array_fill(0, 1, '');
            $rows[] = ['Emisor'];
            $rows[] = ['Nombre', 'RegimenFiscal', 'Rfc'];
            $attrsEmisor = $emisor->attributes();
            $rows[] = [
                (string) $attrsEmisor['Nombre'], (string) $attrsEmisor['RegimenFiscal'],
                (string) $attrsEmisor['Rfc']
            ];

            // Sección: Receptor
            $rows[] = array_fill(0, 1, '');
            $rows[] = ['Receptor'];
            $rows[] = ['Nombre', 'Rfc', 'UsoCFDI'];
            $attrsReceptor = $receptor->attributes();
            $rows[] = [
                (string) $attrsReceptor['Nombre'], (string) $attrsReceptor['Rfc'],
                (string) $attrsReceptor['UsoCFDI']
            ];

            // Sección: Conceptos
            $rows[] = array_fill(0, 1, '');
            $rows[] = ['Conceptos'];
            $rows[] = ['Cantidad', 'ClaveProdServ', 'ClaveUnidad', 'Descripcion'];

            foreach ($conceptos->children($namespaces['cfdi']) as $concepto) {
                $attrsConcepto = $concepto->attributes();
                $rows[] = [
                    (string) $attrsConcepto['Cantidad'], (string) $attrsConcepto['ClaveProdServ'],
                    (string) $attrsConcepto['ClaveUnidad'], (string) $attrsConcepto['Descripcion']
                ];

                $impuestosNode = $concepto->children($namespaces['cfdi'])->Impuestos ?? null;
                if ($impuestosNode) {
                    $traslados = $impuestosNode->children($namespaces['cfdi'])->Traslados ?? null;
                    if ($traslados) {
                        foreach ($traslados->children($namespaces['cfdi']) as $traslado) {
                            $attrsTraslado = $traslado->attributes();
                            $rows[] = array_fill(0, 1, '');
                            $rows[] = ['Concepto Traslado'];
                            $rows[] = ['Base', 'Importe', 'Impuesto', 'TasaOCuota', 'TipoFactor'];
                            $rows[] = [
                                (string) $attrsTraslado['Base'],
                                (string) $attrsTraslado['Importe'],
                                (string) $attrsTraslado['Impuesto'],
                                (string) $attrsTraslado['TasaOCuota'],
                                (string) $attrsTraslado['TipoFactor'],
                            ];
                        }
                    }
                }
            }

            // Sección: Impuestos
            $rows[] = array_fill(0, 1, '');
            $rows[] = ['Impuestos'];
            $rows[] = ['TotalImpuestosTrasladados'];
            $attrsImpuesto = $impuestos->attributes();
            $rows[] = [(string) $attrsImpuesto['TotalImpuestosTrasladados']];

            $rows[] = array_fill(0, 1, '');
            $rows[] = ['Impuesto Traslados'];
            $rows[] = ['Importe', 'Impuesto', 'TasaOCuota', 'TipoFactor'];
            foreach ($impuestos->children($namespaces['cfdi'])->children($namespaces['cfdi']) as $t) {
                $attrs = $t->attributes();
                $rows[] = [
                    (string) $attrs['Importe'], (string) $attrs['Impuesto'],
                    (string) $attrs['TasaOCuota'], (string) $attrs['TipoFactor']
                ];
            }

            // Sección: Timbre Fiscal Digital
            $rows[] = array_fill(0, 1, '');
            $rows[] = ['Complemento Timbre Fiscal Digital'];
            $rows[] = ['FechaTimbrado', 'NoCertificadoSAT', 'RfcProvCertif', 'SelloCFD', 'SelloSAT', 'UUID', 'Version'];
            $timbres = $complemento->children($namespaces['tfd'] ?? []);
            foreach ($timbres as $timbre) {
                $attrs = $timbre->attributes();
                $rows[] = [
                    (string) $attrs['FechaTimbrado'], (string) $attrs['NoCertificadoSAT'],
                    (string) $attrs['RfcProvCertif'], (string) $attrs['SelloCFD'],
                    (string) $attrs['SelloSAT'], (string) $attrs['UUID'], (string) $attrs['Version']
                ];
            }

            $rows[] = array_fill(0, 1, '');
            $rows[] = array_fill(0, 1, '');
            $rows[] = array_fill(0, 1, '');
            $rows[] = array_fill(0, 1, '');
            $rows[] = array_fill(0, 1, '');
            $allRows = array_merge($allRows, $rows);
        }

        Excel::store(new FacturasExport($headers, $allRows), 'facturas.xlsx', 'public');

        return response()->json([
            'success' => true,
            'path' => Storage::url('facturas.xlsx')
        ]);
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
