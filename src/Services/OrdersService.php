<?php

namespace Services;
use Exception;

class OrdersService
{
    private $dbService;
    private $miraklService;
    private $makroService;
    private $shops;

    public function __construct(DatabaseService $dbService, MiraklService $miraklService, MakroService $makroService)
    {
        $this->dbService = $dbService;
        $this->miraklService = $miraklService;
        $this->makroService = $makroService;
        $this->shops = $this->getShopsId();

    }
    public function getShopsId()
    {
 
        $actionRequest = "SELECT * FROM tbltiendas";
        $shopsRequest = $this->dbService->ejecutarConsulta($actionRequest);
        $arrShops = array();
        if(count($shopsRequest) > 0){
            foreach($shopsRequest as $shop){
                $arrShops[$shop['strCountry']] = $shop['idShop'];
            }
            return $arrShops;
        }
        else return [];
    }

    public function getOrders($userId)
    {
 
        return 'toDo';
    }

    public function orderExists($order)
    {
 
        $actionRequest = "SELECT * FROM tblPedidosAPI WHERE strNumPedido = '$order'";
        $orderRequest = $this->dbService->ejecutarConsulta($actionRequest);
        
        if(count($orderRequest) > 0){
            return true;
        }
        else return false;
    }
    
    public function sincroOrders($market, $state, $limit, $offset){

        if($market && $state){
            switch($market){
               
                //Leroy merlin
                case '21' :
                $ordersRequest = $this->miraklService->getOrders('leroy','ES',$state, $limit, $offset);
                if($ordersRequest['status'] === 'success'){
                    $orders = $ordersRequest['details']['response']->orders;
                    $ordersProcessed = $this->miraklService->processOrders('leroy','21','ES', $orders);
                    if($ordersProcessed['status'] === 'success'){
                        $shop = $this->shops['ES'];
                        $ordersCreated = $this->createOrders($shop,'21',$ordersProcessed['details']['response']);
                        return array("status"=> "succes","details"=> $ordersCreated);
                    }
                    else{
                        return array("status"=> "error","details"=> $ordersProcessed['error']);
                    }

                }
                else{
                    return array("status"=> "error","details"=> $ordersRequest['error']);
                }
                                  
                    
                default :
                    return 'No market defined';
            }

        }
        else{
            return 'Fail Sincro Orders';
        }

    }

    public function getAgent ($idMarket){

        $actionRequest = "SELECT * FROM tblagents WHERE idAgent = '$idMarket'";
        $agentData = $this->dbService->ejecutarConsulta($actionRequest);
        $agent = $agentData[0]['stragent'];
        if($agent){
            return $agent;
        }
        else return false;

    }

    public function createOrders($idShop, $idMarket, $orders){

        $report = array();
        foreach($orders as $orderId => $order){
            //Check si existe en db
            $checkExists = $this->orderExists($orderId);
            //Guardar líneas si no existe
            if($checkExists){
               //toDO
               $report[] = array('Order'=> $orderId, 'Details' => 'Order already imported on DB');
            }
            else{
                foreach($order as $lineaOrder){
                    $createLine = $this->createOrderLine($idShop, $idMarket,$lineaOrder);
                    if($createLine['status'] == 'success'){
                        $report[] = array('Order'=> $orderId, 'Details' => 'Order Line imported');
                    }
                    else{
                        $report[] = array('Order'=> $orderId, 'Details' => $createLine['details']);
                    }
                    
                }
            }
        }
        return $report;
    }

    public function createOrderLine($idShop, $idMarket, $orderData) {

    try{
        $shop = $idShop;
        $agent = $this->getAgent($idMarket); //forma de pago
        $orderId = $orderData['orderId'];
    
        $tipoCliente = 1;
        $sector = null;
        
        $Total_order = $orderData['total'];
        $Total_logistics = $orderData['logistics'];
        $email = $orderData['email'];
        
        //Direccion Envio
        $firtsNameEnvio = $orderData['firstnameEnvio'];
        $LastNameEnvio = $orderData['lastnameEnvio'];
        $phoneNumberEnvio = $orderData['phoneEnvio'];
        $streetEnvio = $orderData['streetEnvio'];
        $cityEnvio =  $orderData['cityEnvio'];
        $zipCodeEnvio =  $orderData['zipcodeEnvio'];
        $provinciaEnvio = $orderData['provinciaEnvio'];
        $countryCode = $orderData['countryEnvio'];
    
        //Direccion Facturacion
        $firtsNameFact = $orderData['firstnameFact'];
        $LastNameFact = $orderData['lastnameFact'];
        $company = $orderData['companyFact'];
        $phoneNumberFact = $orderData['phoneFact'];
        $streetFact = $orderData['streetFact'];
        $cityFact =  $orderData['cityFact'];
        $provinciaFact = $orderData['provinciaFact'];
        $zipCodeFact =  $orderData['zipcodeFact'];
       // $countryCode = $orderData['countryFact'];
        
        $CIF = $orderData['CIF'];
        $isPro = $orderData['isPro'];
        $sector = $orderData['sector'];
        
        $FechaSucces = $orderData['success'];
        $NameProduct = $orderData['title'];
        $ID_Product = $orderData['sku'];
        $EAN_product = $orderData['ean'];
        $Amount_line = $orderData['totalLine'];
        $Product_Qty = $orderData['quantity'];
        
        // Control Carácteres especiales
        //Anyadir en este array todo caracter conflictivo
        $CaracterEspecial = array("'");
        $NameProduct = str_replace($CaracterEspecial, "", $NameProduct);
        $cityEnvio = str_replace($CaracterEspecial, "", $cityEnvio);
        $streetEnvio = str_replace($CaracterEspecial, "", $streetEnvio);
        $cityFact = str_replace($CaracterEspecial, "", $cityFact);
        $streetFact = str_replace($CaracterEspecial, "", $streetFact);
        
        $firtsNameEnvio = str_replace($CaracterEspecial, "", $firtsNameEnvio);
        $LastNameEnvio = str_replace($CaracterEspecial, "", $LastNameEnvio);
        $firtsNameFact = str_replace($CaracterEspecial, "", $firtsNameFact);
        $LastNameFact = str_replace($CaracterEspecial, "", $LastNameFact);
    
        $actionRequest = "INSERT INTO tblPedidosAPI
            (intMarket,
            intIdShop, strNumPedido, strClienteNombre, strClienteApellido, strEmail, strCIF_DNI,
            strDireccionEnvio, strLocalidadEnvio, strCPEnvio, strTelf1Envio, strProvinciaEnvio,
            strDireccionFacturacion, strLocalidadFacturacion, strCPFacturacion, strTelf1Facturacion, strProvinciaFacturacion,
            strReferencia, strEAN, strSKU, strArticulo,
            fltCoste, fltTotal, fltCosteEnvio,
            strDescripcion,
            intUnidades,
            strPais,
            fchFechaPago,
            strMetodoPago, strAgent, intOrderState, intTypeCustomer, strCompanyName, intProfessionalSector) 
        VALUES ('$idMarket',
                    '$shop','$orderId', '$firtsNameEnvio', '$LastNameEnvio', '$email', '$CIF',
                    '$streetEnvio', '$cityEnvio', '$zipCodeEnvio', '$phoneNumberEnvio', '$provinciaEnvio',
                    '$streetFact', '$cityFact', '$zipCodeFact', '$phoneNumberFact', '$provinciaFact',  
                    '$ID_Product', '$EAN_product', '$ID_Product', '$NameProduct',
                    '$Amount_line', '$Total_order', '$Total_logistics',
                    '$NameProduct',
                    '$Product_Qty', 
                    '$countryCode',
                    '$FechaSucces',
                    '$agent', '$agent', '2', '$tipoCliente', '$company', '$sector' )";
    
    
        $insertOrder = $this->dbService->ejecutarConsulta($actionRequest);
        
        return array('status'=> 'success', 'details' => $insertOrder);
        
        
        }
        catch(Exception $e){
            $error = $e->getMessage();
            return array('status' => 'error', 'details' => $error);
           
        }
    }    
    

    

    
}
