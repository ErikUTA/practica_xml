<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FacturasExport;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class XMLController extends Controller
{
    public function convertirFacturaXmlACsv(Request $request)
    {
        \DB::beginTransaction();
        try {
            $userId = $request->get('user_id');
            $user = User::find($userId);
            if(!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Es necesario estar registrado para utilizar el interprete XML.'
                ], 500);
            }

            $request->validate([
                'xml_files' => 'required|array',
                'xml_files.*' => 'file|mimes:xml',
            ]);

            $headers = [
                'Certificado', 'Fecha', 'Folio', 'FormaPago', 'LugarExpedicion',
                'MetodoPago', 'Moneda', 'NoCertificado', 'Sello', 'Serie',
                'SubTotal', 'TipoDeComprobante', 'Total', 'Version',
                'Nombre_Emisor', 'RegimenFiscal_Emisor', 'Rfc_Emisor',
                'Nombre_Receptor', 'Rfc_Receptor', 'UsoCFDI',
                'Cantidad', 'ClaveProdServ', 'ClaveUnidad', 'Descripcion',
                'Base', 'Importe_Concepto', 'Impuesto_Concepto', 'TasaOCuota_Concepto', 'TipoFactor_Concepto',
                'TotalImpuestosTrasladados',
                'Importe_Global', 'Impuesto_Global', 'TasaOCuota_Global', 'TipoFactor_Global',
                'FechaTimbrado', 'NoCertificadoSAT', 'RfcProvCertif', 'SelloCFD', 'SelloSAT', 'UUID', 'Version_TFD',
                'ModalidadServicio', 'FechaCarga', 'RFC', 'Instrumento', 'PersonalidadUsuario'
            ];

            $rows = [];

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

                $row = [];

                $row[] = (string) $xml['Certificado'];
                $row[] = (string) $xml['Fecha'];
                $row[] = (string) $xml['Folio'];
                $row[] = (string) $xml['FormaPago'];
                $row[] = (string) $xml['LugarExpedicion'];
                $row[] = (string) $xml['MetodoPago'];
                $row[] = (string) $xml['Moneda'];
                $row[] = (string) $xml['NoCertificado'];
                $row[] = (string) $xml['Sello'];
                $row[] = (string) $xml['Serie'];
                $row[] = (string) $xml['SubTotal'];
                $row[] = (string) $xml['TipoDeComprobante'];
                $row[] = (string) $xml['Total'];
                $row[] = (string) $xml['Version'];

                $attrsEmisor = $emisor->attributes();
                $row[] = (string) $attrsEmisor['Nombre'];
                $row[] = (string) $attrsEmisor['RegimenFiscal'];
                $row[] = (string) $attrsEmisor['Rfc'];

                $attrsReceptor = $receptor->attributes();
                $row[] = (string) $attrsReceptor['Nombre'];
                $row[] = (string) $attrsReceptor['Rfc'];
                $row[] = (string) $attrsReceptor['UsoCFDI'];

                $concepto = $conceptos->children($namespaces['cfdi']) ?? null;
                if ($concepto) {
                    $attrsConcepto = $concepto->attributes();
                    $row[] = (string) $attrsConcepto['Cantidad'];
                    $row[] = (string) $attrsConcepto['ClaveProdServ'];
                    $row[] = (string) $attrsConcepto['ClaveUnidad'];
                    $row[] = (string) $attrsConcepto['Descripcion'];

                    $traslado = $concepto->children($namespaces['cfdi'])->Impuestos->Traslados->children($namespaces['cfdi']) ?? null;
                    if ($traslado) {
                        $a = $traslado->attributes();
                        $row[] = (string) $a['Base'];
                        $row[] = (string) $a['Importe'];
                        $row[] = (string) $a['Impuesto'];
                        $row[] = (string) $a['TasaOCuota'];
                        $row[] = (string) $a['TipoFactor'];
                    } else {
                        $row = array_merge($row, ['', '', '', '', '']);
                    }
                } else {
                    $row = array_merge($row, ['', '', '', '', '', '', '', '', '', '']);
                }

                $attrsImp = $impuestos->attributes();
                $row[] = (string) $attrsImp['TotalImpuestosTrasladados'];

                $trasladoGlobal = $impuestos->Traslados->children($namespaces['cfdi']) ?? null;

                if ($trasladoGlobal) {
                    $a = $trasladoGlobal->attributes();
                    $row[] = (string) $a['Importe'];
                    $row[] = (string) $a['Impuesto'];
                    $row[] = (string) $a['TasaOCuota'];
                    $row[] = (string) $a['TipoFactor'];
                } else {
                    $row = array_merge($row, ['', '', '', '']);
                }

                $timbre = $complemento->children($namespaces['tfd'] ?? [])->TimbreFiscalDigital ?? null;

                if ($timbre) {
                    $a = $timbre->attributes();
                    $row[] = (string) $a['FechaTimbrado'];
                    $row[] = (string) $a['NoCertificadoSAT'];
                    $row[] = (string) $a['RfcProvCertif'];
                    $row[] = (string) $a['SelloCFD'];
                    $row[] = (string) $a['SelloSAT'];
                    $row[] = (string) $a['UUID'];
                    $row[] = (string) $a['Version'];
                } else {
                    $row = array_merge($row, ['', '', '', '', '', '', '']);
                }

                $row[] = (string) $request->get('ModalidadServicio');
                $row[] = (string) $request->get('FechaCarga');
                $row[] = (string) $request->get('RFC');
                $row[] = (string) $request->get('Instrumento');
                $row[] = (string) $request->get('PersonalidadUsuario');

                $rows[] = $row;
            }

            Excel::store(new FacturasExport($headers, array_merge([$headers], $rows)), 'facturas/facturas.xlsx', 'local');

            \DB::commit();
            return response()->json([
                'success' => true,
                'path' => Storage::url('facturas/facturas.xlsx'),
            ], 200);
        } catch(AuthorizationException $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Throwable $exception) {
            \DB::rollBack();
            return response()->json([
                'error' => $exception->getMessage(),
                'line' => $exception->getLine()
            ], 500);
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
