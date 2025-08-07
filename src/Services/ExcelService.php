<?php
namespace Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelService
{
    public function createOrdersFile ($orders)
    {
        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        $activeWorksheet->setCellValue('A1', 'strNumPedido')
        ->setCellValue('B1', 'intIdShop')
        ->setCellValue('C1', 'strClienteNombre')
        ->setCellValue('D1', 'strClienteApellido')
        ->setCellValue('E1', 'strEmail')
        ->setCellValue('F1', 'strCIF_DNI')
        ->setCellValue('G1', 'strDireccionEnvio')
        ->setCellValue('H1', 'strLocalidadEnvio')
        ->setCellValue('I1', 'strProvinciaEnvio')
        ->setCellValue('J1', 'strCPEnvio')
        ->setCellValue('K1', 'strTelf1Envio')
        ->setCellValue('L1', 'strTelf2Envio')
        ->setCellValue('M1', 'strDireccionFacturacion')
        ->setCellValue('N1', 'strLocalidadFacturacion')
        ->setCellValue('O1', 'strProvinciaFacturacion')
        ->setCellValue('P1', 'strCPFacturacion')
        ->setCellValue('Q1', 'strTelf1Facturacion')
        ->setCellValue('R1', 'strTelf2Facturacion')
        ->setCellValue('S1', 'strReferencia')
        ->setCellValue('T1', 'strEAN')
        ->setCellValue('U1', 'strSKU')
        ->setCellValue('V1', 'strArticulo')
        ->setCellValue('W1', 'fltCoste')
        ->setCellValue('X1', 'fltTotal')
        ->setCellValue('Y1', 'fltCosteEnvio')
        ->setCellValue('Z1', 'strDescripcion')
        ->setCellValue('AA1', 'intUnidades')
        ->setCellValue('AB1', 'intUnidadesServ')
        ->setCellValue('AC1', 'strCarrier')
        ->setCellValue('AD1', 'strTracking')
        ->setCellValue('AE1', 'strInfoCarrier1')
        ->setCellValue('AF1', 'strInfoCarrier2')
        ->setCellValue('AG1', 'strInfoCarrier3')
        ->setCellValue('AH1', 'strInfoCarrier4')
        ->setCellValue('AI1', 'strInfoCarrier5')
        ->setCellValue('AJ1', 'strPais')
        ->setCellValue('AK1', 'strMetodoPago')
        ->setCellValue('AL1', 'strAgent')
        ->setCellValue('AM1', 'intOrderState')
        ->setCellValue('AN1', 'intTypeCustomer')
        ->setCellValue('AO1', 'strCompanyName')
        ->setCellValue('AP1', 'intProfessionalSector');

        foreach ($orders as $orderIndex => $datoOrder){

            $telfEnvio2 = $datoOrder['strTelf2Envio'];
            $telfFactura2 = $datoOrder['strTelf2Facturacion'];
            if($telfEnvio2 == null){
                $telfEnvio2 = $datoOrder['strTelf1Envio'];
            }
            if($telfFactura2 == null){
                $telfFactura2 = $datoOrder['strTelf1Facturacion'];
            }
            
            $activeWorksheet->setCellValueExplicit('A'.($orderIndex+2), (string) $datoOrder['strNumPedido'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)
                ->setCellValue('B'.($orderIndex+2), $datoOrder['intIdShop'])
                ->setCellValue('C'.($orderIndex+2), $datoOrder['strClienteNombre'])
                ->setCellValue('D'.($orderIndex+2), $datoOrder['strClienteApellido'])
                ->setCellValue('E'.($orderIndex+2), $datoOrder['strEmail'])
                ->setCellValue('F'.($orderIndex+2), $datoOrder['strCIF_DNI'])
                ->setCellValue('G'.($orderIndex+2), $datoOrder['strDireccionEnvio'])
                ->setCellValue('H'.($orderIndex+2), $datoOrder['strLocalidadEnvio'])
                ->setCellValue('I'.($orderIndex+2), $datoOrder['strProvinciaEnvio'])
                ->setCellValue('J'.($orderIndex+2), $datoOrder['strCPEnvio'])
                ->setCellValue('K'.($orderIndex+2), $datoOrder['strTelf1Envio'])
                ->setCellValue('L'.($orderIndex+2), $telfEnvio2)
                ->setCellValue('M'.($orderIndex+2), $datoOrder['strDireccionFacturacion'])
                ->setCellValue('N'.($orderIndex+2), $datoOrder['strLocalidadFacturacion'])
                ->setCellValue('O'.($orderIndex+2), $datoOrder['strProvinciaFacturacion'])
                ->setCellValue('P'.($orderIndex+2), $datoOrder['strCPFacturacion'])
                ->setCellValue('Q'.($orderIndex+2), $datoOrder['strTelf1Facturacion'])
                ->setCellValue('R'.($orderIndex+2), $telfFactura2)
                ->setCellValue('S'.($orderIndex+2), $datoOrder['strReferencia'])
                ->setCellValue('T'.($orderIndex+2), $datoOrder['strEAN'])
                ->setCellValue('U'.($orderIndex+2), $datoOrder['strSKU'])
                ->setCellValue('V'.($orderIndex+2), $datoOrder['strArticulo'])
                ->setCellValue('W'.($orderIndex+2), $datoOrder['fltCoste'])
                ->setCellValue('X'.($orderIndex+2), $datoOrder['fltTotal'])
                ->setCellValue('Y'.($orderIndex+2), $datoOrder['fltCosteEnvio'])
                ->setCellValue('Z'.($orderIndex+2), $datoOrder['strDescripcion'])
                ->setCellValue('AA'.($orderIndex+2), $datoOrder['intUnidades'])
                ->setCellValue('AB'.($orderIndex+2), $datoOrder['intUnidadesServ'])   
                ->setCellValue('AC'.($orderIndex+2), $datoOrder['strCarrier'])
                ->setCellValue('AD'.($orderIndex+2), $datoOrder['strTracking'])   
                ->setCellValue('AE'.($orderIndex+2), $datoOrder['strInfoCarrier1'])   
                ->setCellValue('AF'.($orderIndex+2), $datoOrder['strInfoCarrier2'])   
                ->setCellValue('AG'.($orderIndex+2), $datoOrder['strInfoCarrier3'])   
                ->setCellValue('AH'.($orderIndex+2), $datoOrder['strInfoCarrier4'])   
                ->setCellValue('AI'.($orderIndex+2), $datoOrder['strInfoCarrier5'])   
                ->setCellValue('AJ'.($orderIndex+2), $datoOrder['strPais'])   
                ->setCellValue('AK'.($orderIndex+2), $datoOrder['strMetodoPago'])   
                ->setCellValue('AL'.($orderIndex+2), $datoOrder['strAgent'])   
                ->setCellValue('AM'.($orderIndex+2), $datoOrder['intOrderState'])   
                ->setCellValue('AN'.($orderIndex+2), $datoOrder['intTypeCustomer'])   
                ->setCellValue('AO'.($orderIndex+2), $datoOrder['strCompanyName'])   
                ->setCellValue('AP'.($orderIndex+2), $datoOrder['intProfessionalSector']);
        }

        $tempFile = fopen('php://memory', 'r+');
        $writer = new Xlsx($spreadsheet);
        $writer->save( $tempFile);

        rewind($tempFile);

        return $tempFile;
    }
}