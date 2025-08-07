<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Xml extends Model
{
    protected $table = 'xml_invoices';

    protected $fillable = [
        'Certificado',
        'Fecha',
        'Folio',
        'FormaPago',
        'LugarExpedicion',
        'MetodoPago',
        'Moneda',
        'NoCertificado',
        'Sello',
        'Serie',
        'SubTotal',
        'TipoDeComprobante',
        'Total',
        'Version',
        'Nombre_Emisor',
        'RegimenFiscal_Emisor',
        'Rfc_Emisor',
        'Nombre_Receptor',
        'Rfc_Receptor',
        'UsoCFDI',
        'Cantidad',
        'ClaveProdServ',
        'ClaveUnidad',
        'Descripcion',
        'Base',
        'Importe_Concepto',
        'Impuesto_Concepto',
        'TasaOCuota_Concepto',
        'TipoFactor_Concepto',
        'TotalImpuestosTrasladados',
        'Importe_Global',
        'Impuesto_Global',
        'TasaOCuota_Global',
        'TipoFactor_Global',
        'FechaTimbrado',
        'NoCertificadoSAT',
        'RfcProvCertif',
        'SelloCFD',
        'SelloSAT',
        'UUID',
        'Version_TFD',
        'ModalidadServicio',
        'FechaCarga',
        'RFC',
        'Instrumento',
        'PersonalidadUsuario'
    ];
}
