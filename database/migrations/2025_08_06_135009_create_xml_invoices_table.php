<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('xml_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('Certificado')->nullable();
            $table->string('Fecha')->nullable();
            $table->string('Folio')->nullable();
            $table->string('FormaPago')->nullable();
            $table->string('LugarExpedicion')->nullable();
            $table->string('MetodoPago')->nullable();
            $table->string('Moneda')->nullable();
            $table->string('NoCertificado')->nullable();
            $table->string('Sello')->nullable();
            $table->string('Serie')->nullable();
            $table->string('SubTotal')->nullable();
            $table->string('TipoDeComprobante')->nullable();
            $table->string('Total')->nullable();
            $table->string('Version')->nullable();
            
            $table->string('Nombre_Emisor')->nullable();
            $table->string('RegimenFiscal_Emisor')->nullable();
            $table->string('Rfc_Emisor')->nullable();

            $table->string('Nombre_Receptor')->nullable();
            $table->string('Rfc_Receptor')->nullable();
            $table->string('UsoCFDI')->nullable();

            $table->string('Cantidad')->nullable();
            $table->string('ClaveProdServ')->nullable();
            $table->string('ClaveUnidad')->nullable();
            $table->string('Descripcion')->nullable();

            $table->string('Base')->nullable();
            $table->string('Importe_Concepto')->nullable();
            $table->string('Impuesto_Concepto')->nullable();
            $table->string('TasaOCuota_Concepto')->nullable();
            $table->string('TipoFactor_Concepto')->nullable();

            $table->string('TotalImpuestosTrasladados')->nullable();
            $table->string('Importe_Global')->nullable();
            $table->string('Impuesto_Global')->nullable();
            $table->string('TasaOCuota_Global')->nullable();
            $table->string('TipoFactor_Global')->nullable();

            $table->string('FechaTimbrado')->nullable();
            $table->string('NoCertificadoSAT')->nullable();
            $table->string('RfcProvCertif')->nullable();
            $table->string('SelloCFD')->nullable();
            $table->string('SelloSAT')->nullable();
            $table->string('UUID')->nullable();
            $table->string('Version_TFD')->nullable();

            $table->string('ModalidadServicio')->nullable();
            $table->string('FechaCarga')->nullable();
            $table->string('RFC')->nullable();
            $table->string('Instrumento')->nullable();
            $table->string('PersonalidadUsuario')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_invoices');
    }
};
