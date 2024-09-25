<?php

namespace Services;

use Services\MailService;

class ManomanoService
{
    private $dbService;
    private $mailService;

    public function __construct(DatabaseService $dbService, MailService $mailService)
    {
        $this->dbService = $dbService;
        $this->mailService = $mailService;
    }

    public function getOrderMail ($idOrderMarket) {

        $consulta = "SELECT * FROM tblfacturasapp WHERE strIdMarket = '$idOrderMarket'";
        $resultado = $this->dbService->ejecutarConsulta($consulta);
        if (count($resultado) > 0) {
            // Obtén la fila como un objeto
            $fila = $resultado[0];
            $mail = $fila['strMail'];
            if($mail) return $mail;
            else return false;
            
        }
        else{
            return false;
        }

    }

     
     
 
    /* ---------------------------------
                   Facturas
     --------------------------------- */

    //Manomano no tiene API para cargar facturas, exige enviarlas por correo con el asunto Factura + numero pedido
    public function sendInvoice($idOrderMarket, $country, $invoice){

       
        $country = strtolower($country);

        $fileName = 'invoice-'.$idOrderMarket.'.pdf';

        $mail = $this->getOrderMail($idOrderMarket);

        $body = '<p>Buenos días</p><p>Adjuntamos la factura de su pedido en Manomano '.$idOrderMarket.' : </p><p>Saludos</p>';
        $subject = 'Factura '.$idOrderMarket;

        //Enviar por correo
        $result = $this->mailService->sendEmail($mail, $subject, $body, $fileName, $invoice);

        if($result['status'] == 'success'){
            return array("status"=> "success","details"=> array("response" => $result));
        }
        else{
            return array("status"=> "error","details"=> array("response" => $result));
        }
        


               

        
        
        
    }

    
    /* ---------------------------------
                FIN   Facturas
     --------------------------------- */

    
}
