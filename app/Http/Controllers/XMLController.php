<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Permission;
use App\Models\PermissionServiceUser;
use App\Models\Xml;
use App\Models\Service;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InvoicesExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class XMLController extends Controller
{
    use AuthorizesRequests;

    public function convertInvoiceToXml(Request $request)
    {
        \DB::beginTransaction();
        try {
            $user = Auth::user();
            if(!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Es necesario estar registrado para utilizar el interprete XML.'
                ], 500);
            }

            $request->validate([
                'xml_files' => 'required|array',
                'xml_files.*' => 'file|mimes:xml',
                'ModalidadServicio' => 'required|string',
                'FechaCarga' => 'required|string',
                'RFC' => 'required|string',
                'LibroContable' => 'required|string',
                'Instrumento' => 'required|string',
                'PersonalidadUsuario' => 'required|string',
            ], [
                'xml_files.required' => 'Por favor seleccione un archivo.',
                'ModalidadServicio.required' => 'Por favor seleccione un servicio.',
                'FechaCarga.required' => 'Por favor seleccione una fecha.',
                'RFC.required' => 'El RFC es requerido.',
                'LibroContable.required' => 'El libro contable es requerido.',
                'Instrumento.required' => 'El instrumento es requerido.',
                'PersonalidadUsuario.required' => 'El campo Personalidad Usuario es requerido.',
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
                'ModalidadServicio', 'FechaCarga', 'RFC', 'LibroContable', 'Instrumento', 'PersonalidadUsuario'
            ];

            $rows = [];

            $plan = PermissionServiceUser::where('user_id', $user->id)
                ->where('service_id', Service::XMl)
                ->first();

            $limit = 0;

            if($plan->permission_id === Permission::BASIC) {
                $limit = Permission::PLAN_BASIC;
            } elseif ($plan->permission_id === Permission::INTERMEDIATE) {
                $limit = Permission::PLAN_INTERMEDIATE;
            } elseif ($plan->permission_id === Permission::ADVANCED) {
                $limit = Permission::PLAN_ADVANCED;
            }

            foreach ($request->file('xml_files') as $file) {
                $xml = simplexml_load_file($file->getRealPath());
                if (!$xml) continue;

                $namespaces = $xml->getNamespaces(true);
                if ($xml->getName() === 'Comprobante') {
                    $totalFacturas = 1;
                } else {
                    $comprobantes = $xml->xpath('//cfdi:Comprobante');
                    $totalFacturas = count($comprobantes);
                }
                if ($totalFacturas > $limit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ha excedido el límite de facturas.',
                    ], 500);
                }

                $cfdi = $xml->children($namespaces['cfdi']);
                $emisor = $cfdi->Emisor ?? null;
                $receptor = $cfdi->Receptor ?? null;
                $impuestos = $cfdi->Impuestos ?? null;
                $complemento = $cfdi->Complemento ?? null;
                $conceptos = $cfdi->Conceptos ?? null;

                $row = [];
                $version = (string) $xml['Version'];

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
                $row[] = $version;

                $attrsEmisor = $emisor->attributes();
                $row[] = (string) $attrsEmisor['Nombre'];
                $row[] = (string) $attrsEmisor['RegimenFiscal'];
                $row[] = (string) $attrsEmisor['Rfc'];

                $attrsReceptor = $receptor->attributes();
                $row[] = (string) $attrsReceptor['Nombre'];
                $row[] = (string) $attrsReceptor['Rfc'];
                $row[] = (string) $attrsReceptor['UsoCFDI'];

                if(((string) $attrsReceptor['Rfc']) !== $request->get('RFC')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El RFC del receptor no coincide con el RFC del cliente.',
                    ], 500);
                }

                $concepto = $conceptos->children($namespaces['cfdi']) ?? null;
                if ($concepto) {
                    $attrsConcepto = $concepto->attributes();
                    $row[] = (string) $attrsConcepto['Cantidad'];
                    $row[] = (string) $attrsConcepto['ClaveProdServ'];
                    $row[] = (string) $attrsConcepto['ClaveUnidad'];
                    $row[] = (string) $attrsConcepto['Descripcion'];

                    $traslado = [];
                    if($concepto->children($namespaces['cfdi'])->Impuestos->Traslados) {
                        $traslado = $concepto->children($namespaces['cfdi'])->Impuestos->Traslados->children($namespaces['cfdi']) ?? null;
                    }

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

                $trasladoGlobal = [];
                if($impuestos->Traslados) {
                    $trasladoGlobal = $impuestos->Traslados->children($namespaces['cfdi']) ?? null;
                }

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
                    $uuid = (string) $a['UUID'];
                    $exist = Xml::where('UUID', $a['UUID'])->first();
                    if($exist) {
                        return response()->json([
                            'success' => false,
                            'message' => 'La factura ya ha sido leída recientemente.'
                        ], 500);
                    }
                    if (Storage::disk('local')->exists("facturas/factura_{$uuid}.xlsx")) {
                        return response()->json([
                            'success' => false,
                            'message' => 'El archivo ya fue generado anteriormente.',
                            'path' => Storage::url("facturas/factura_{$uuid}.xlsx")
                        ], 500);
                    }
                } else {
                    $row = array_merge($row, ['', '', '', '', '', '', '']);
                }

                $row[] = (string) $request->get('ModalidadServicio');
                $row[] = (string) $request->get('FechaCarga');
                $row[] = (string) $request->get('RFC');
                $row[] = (string) $request->get('LibroContable');
                $row[] = (string) $request->get('Instrumento');
                $row[] = (string) $request->get('PersonalidadUsuario');

                $rows[] = $row;

                if (count($headers) === count($row)) {
                    $data = array_combine($headers, $row);
                    Xml::create($data);
                } else {
                    throw new \Exception('La cantidad de campos no coincide con la cantidad de valores.');
                }
            }

            Excel::store(new InvoicesExport($headers, array_merge([$headers], $rows)), "facturas/factura_{$uuid}.xlsx", 'local');

            \DB::commit();
            return response()->json([
                'success' => true,
                'path' => Storage::url("facturas/factura_{$uuid}.xlsx"),
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

    public function getXmlServiceInformation()
    {
        $xmlService = Service::where('id', Service::XMl)->firstOrFail();

        return response()->json([
            'success' => true,
            'xmlService' => $xmlService,
        ], 200);
    }

}