<?php

namespace Services;
use Exception;

class OrdersService
{
    private $dbService;
    private $miraklService;
    private $makroService;
    private $miraviaService;
    private $kuantoService;
    private $ankorService;
    private $excelService;
    private $shops;

    public function __construct(
        DatabaseService $dbService, 
        MiraklService $miraklService, 
        MakroService $makroService, 
        MiraviaService $miraviaService, 
        KuantoService $kuantoService, 
        AnkorService $ankorService,
        ExcelService $excelService)
    {
        $this->dbService = $dbService;
        $this->miraklService = $miraklService;
        $this->makroService = $makroService;
        $this->miraviaService = $miraviaService;
        $this->kuantoService = $kuantoService;
        $this->ankorService = $ankorService;
        $this->excelService = $excelService;
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

    public function getOrders($market, $limit )
    {
        if($market === '0') $action = "SELECT DISTINCT strNumPedido, intMarket, strPais, strDireccionEnvio, fchFechaPago, intEstado, fchFechaImport, strClienteNombre, fltTotal FROM tblPedidosAPI ORDER BY intEstado ASC, fchFechaPago DESC LIMIT $limit ";
        else $action = "SELECT DISTINCT strNumPedido, intMarket, strPais, strDireccionEnvio, fchFechaPago, intEstado, fchFechaImport, strClienteNombre, fltTotal FROM tblPedidosAPI  WHERE intMarket = $market ORDER BY intEstado ASC, fchFechaPago DESC LIMIT $limit ";

        $ordersData = $this->dbService->ejecutarConsulta($action);
        $this->dbService->cerrarConexion();
        return $ordersData;
    }
    public function getCarriers()
    {
        $action = "SELECT DISTINCT strLabel, strUrl FROM tblcarriers";

        $carriersData = $this->dbService->ejecutarConsulta($action);
        $this->dbService->cerrarConexion();
        return $carriersData;
    }

    public function getOrderData($orders)
    {
        if(is_array($orders)){
            $stringOrders = $this->createStringOrders($orders);
            $action = "SELECT * FROM tblPedidosAPI  WHERE strNumPedido IN ($stringOrders)  ";
        }
        else{
            $action = "SELECT * FROM tblPedidosAPI  WHERE strNumPedido = '$orders' ";
        }
        $ordersData = $this->dbService->ejecutarConsulta($action);
        return $ordersData;
    }
    public function getOrdersSend($orders )
    {
        if(is_array($orders)){
            $stringOrders = $this->createStringOrders($orders);
            $action = "SELECT DISTINCT P.strNumPedido, P.intMarket, P.strPais, P.strClienteNombre, P.strClienteApellido , 
                        COALESCE(I.strCarrier, '') AS strCarrier, 
                        COALESCE(I.strTracking, '') AS strTracking
                    FROM tblPedidosAPI P
                    LEFT JOIN tblfacturasapp I 
                    ON P.strNumPedido = I.strIdMarket
                    WHERE P.strNumPedido 
                    IN ($stringOrders) ";
        }
        else{
            $action = "SELECT DISTINCT strNumPedido, intMarket, strPais, strClienteNombre, strClienteApellido FROM tblPedidosAPI  WHERE strNumPedido = '$orders' ";
        }
        $ordersData = $this->dbService->ejecutarConsulta($action);
        $this->dbService->cerrarConexion();
        return $ordersData;
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

    public function updateOrderState($orders, $market, $state, $date = null) {

        if(is_array($orders)){
            $stringOrders = $this->createStringOrders($orders);
            if($date){
                $action = "UPDATE tblPedidosAPI SET intEstado='$state', fchFechaImport = '$date' WHERE strNumPedido IN ($stringOrders) ";
            }
            else{
                $action = "UPDATE tblPedidosAPI SET intEstado='$state' WHERE strNumPedido IN ($stringOrders) ";
            }
        }
        else{
            $action = "UPDATE tblPedidosAPI SET intEstado='$state' WHERE strNumPedido = '$orders'";
        }
        
        $this->dbService->ejecutarConsulta($action);
        $this->dbService->cerrarConexion();

        return array("status"=> "success","details"=> $orders);
        
    }
    
    public function sincroOrders($market, $country, $state, $limit, $offset){

        if($market && $state){
            switch($market){
               
                //Kuanto
                case '17' :
                    $ordersRequest = $this->kuantoService->getOrders( $state, $limit, $offset);
                    if($ordersRequest['status'] === 'success'){
                        $orders = $ordersRequest['details']['response'];
                        $ordersProcessed = $this->kuantoService->processOrders('17',$country, $orders);
                        if($ordersProcessed['status'] === 'success'){
                            //$shop = $this->shops[$country];
                            $ordersCreated = $this->createOrders('17',$ordersProcessed['details']['response']);
                            return array("status"=> "success","details"=> $ordersCreated);
                        }
                        else{
                            return array("status"=> "error","details"=> $ordersProcessed['details']);
                        }

                    }
                    else{
                        return array("status"=> "error","details"=> $ordersRequest['details']);
                    }
                //Leroy merlin
                case '21' :
                    $leroyCountry = ['ES', 'IT', 'PT'];
                    if($country === null || !in_array($country, $leroyCountry)) break;
                    $ordersRequest = $this->miraklService->getOrders('leroy',$country, $state, $limit, $offset);
                    if($ordersRequest['status'] === 'success'){
                        $orders = $ordersRequest['details']['response']->orders;
                        $ordersProcessed = $this->miraklService->processOrders('leroy','21',$country, $orders);
                        if($ordersProcessed['status'] === 'success'){
                            //$shop = $this->shops[$country];
                            $ordersCreated = $this->createOrders('21',$ordersProcessed['details']['response']);
                            return array("status"=> "success","details"=> $ordersCreated);
                        }
                        else{
                            return array("status"=> "error","details"=> $ordersProcessed['details']);
                        }

                    }
                    else{
                        return array("status"=> "error","details"=> $ordersRequest['details']);
                    }
                //Miravia 
                case '30':
                    $ordersRequest = $this->miraviaService->getOrders( $state, $limit, $offset);
                    if($ordersRequest['status'] === 'success'){
                        $orders = $ordersRequest['details']['response']->data->orders;
                        
                        $ordersProcessed = $this->miraviaService->processOrders('30',$country, $orders);
                        
                        if($ordersProcessed['status'] === 'success'){
                            //$shop = $this->shops[$country];
                            $ordersCreated = $this->createOrders('30',$ordersProcessed['details']['response']);
                            return array("status"=> "success","details"=> $ordersCreated);
                        }
                        else{
                            return array("status"=> "error","details"=> $ordersProcessed['details']);
                        }
                        
                    }
                    else{
                        return array("status"=> "error","details"=> $ordersRequest['details']);
                    }
                //Makro
                case '28':
                    $ordersRequest = $this->makroService->getOrders(false, $state, $limit, $offset);
                    if($ordersRequest['status'] === 'success'){
                        
                        $orders = $ordersRequest['details']->items;
                        
                        $ordersProcessed = $this->makroService->processOrders('28',$country, $orders);
                        
                        if($ordersProcessed['status'] === 'success'){
                           // $shop = $this->shops[$country];
                            $ordersCreated = $this->createOrders('28',$ordersProcessed['details']['response']);
                            return array("status"=> "success","details"=> $ordersCreated);
                        }
                        else{
                            return array("status"=> "error","details"=> $ordersProcessed['details']);
                        }
                        
                    }
                    else{
                        return array("status"=> "error","details"=> $ordersRequest['details']);
                    }
                    //AnkorStore
                case '32':
                    $ordersRequest = $this->ankorService->getOrders($state, $limit, $offset);
                    if($ordersRequest['status'] === 'success'){
                        
                        $orders = $ordersRequest['details']['response']->data;
                        
                        $ordersProcessed = $this->ankorService->processOrders('32',$country, $orders);
                        
                        if($ordersProcessed['status'] === 'success'){
                            //$shop = $this->shops[$country];
                            $ordersCreated = $this->createOrders('32',$ordersProcessed['details']['response']);
                            return array("status"=> "success","details"=> $ordersCreated);
                        }
                        else{
                            return array("status"=> "error","details"=> $ordersProcessed['details']);
                        }
                        
                    }
                    else{
                        return array("status"=> "error","details"=> $ordersRequest['details']);
                    }      
                    
                default :
                return array("status"=> "error","details"=> 'No market defined');
            }
            return array("status"=> "error","details"=> 'No market defined');
        }
        else{
            return array("status"=> "error","details"=> 'Fail OrdersSincro');
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

    public function createOrders( $idMarket, $orders){

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
                    $country = $lineaOrder['countryEnvio'];
                    $shop = $this->shops[$country];
                    $createLine = $this->createOrderLine($shop, $idMarket,$lineaOrder);
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
    
        $tipoCliente = $orderData['tipoCliente'];
        $sector = $orderData['sector'];
        
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
        $idMarketLine = $orderData['idLine'];
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
            strInfoCarrier5,
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
                    '$idMarketLine',
                    '$agent', '$agent', '2', '$tipoCliente', '$company', '$sector' )";
    
    
        $insertOrder = $this->dbService->ejecutarConsulta($actionRequest);
        
        return array('status'=> 'success', 'details' => $insertOrder);
        
        
        }
        catch(Exception $e){
            $error = $e->getMessage();
            return array('status' => 'error', 'details' => $error);
           
        }
    } 

    public function createStringOrders($orders){
        $stringOrders = "'";
        $stringOrders .= implode("','",$orders);
        $stringOrders .= "'";
        return $stringOrders;
    }
    
    public function createOrdersFile ($orders) {
        $ordersData = $this->getOrderData($orders);
        $ordersFileStream = $this->excelService->createOrdersFile($ordersData);
        $date = date('Y-m-d');
        $updateState = $this->updateOrderState($orders, null, 2, $date);
        return $ordersFileStream;
    }

    public function ordersSendData ($orders){

        //get ordersData
        $ordersData = $this->getOrdersSend($orders);
        //getTrackings
        return $ordersData;

    }

    public function ordersSend ($ordersData) {

        $report = array();

        foreach($ordersData as $orderData){

            $market = $orderData['intMarket'];
            $country = $orderData['strPais'];
            $order = $orderData['strNumPedido'];
            $carrier = $orderData['strCarrier'];
            $tracking = $orderData['strTracking'];
            $update = isset($orderData['update']) ? $orderData['update'] : false;

            try{
            
            switch($market){
                //Kuanto
                case '17' :
                    $sendRequest = $this->kuantoService->sendConfirm($order, $carrier, $tracking);
                    if($sendRequest['status'] === 'success'){
                        $updateState = $this->updateOrderState($order, null, 3);
                        $report[] = array( "order" => $order, "status"=> "success","details"=> $sendRequest['details']);
                    }
                    else{
                        $report[] = array( "order" => $order, "status"=> "error","details"=> $sendRequest['details']);
                    }
                    break;
                //Leroy merlin
                case '21' :
                    $trackingRequest = $this->miraklService->sendTracking("leroy", $market, $country, $order, $carrier, $tracking);
                    if($trackingRequest['status'] === 'success'){
                        $report[] = array( "order" => $order, "status"=> "success","details"=> $trackingRequest['details']);
                    }
                    else{
                        $report[] = array( "order" => $order, "status"=> "error","details"=> $trackingRequest['details']);
                    }
                    if(!$update)
                    {
                        $sendRequest = $this->miraklService->sendConfirm("leroy", $country, $order);
                        if($sendRequest['status'] === 'success'){
                            $updateState = $this->updateOrderState($order, null, 3);
                            $report[] = array( "order" => $order, "status"=> "success","details"=> $sendRequest['details']);
                        }
                        else{
                            $report[] = array( "order" => $order, "status"=> "error","details"=> $sendRequest['details']);
                        }
                    }
                    break;
                    
                //Makro
                case '28':
                    $sendRequest = $this->makroService->sendConfirm($order, $carrier, $tracking);
                    if($sendRequest['status'] === 'success'){
                        $updateState = $this->updateOrderState($order, null, 3);
                        $report[] = array( "order" => $order, "status"=> "success","details"=> $sendRequest['details']);
                    }
                    else{
                        $report[] = array( "order" => $order, "status"=> "error","details"=> $sendRequest['details']);
                    }
                    break;
                //Miravia 
                case '30':
                    $sendRequest = $this->miraviaService->sendConfirm($order, $carrier, $tracking);
                    if($sendRequest['status'] === 'success'){
                        $updateState = $this->updateOrderState($order, null, 3);
                        $report[] = array( "order" => $order, "status"=> "success","details"=> $sendRequest['details']);
                    }
                    else{
                        $report[] = array( "order" => $order, "status"=> "error","details"=> $sendRequest['details']);
                    }
                    break;
                //AnkorStore
                case '32':
                    $sendRequest = $this->ankorService->sendConfirm($order, $carrier, $tracking);
                    if($sendRequest['status'] === 'success'){
                        $updateState = $this->updateOrderState($order, null, 3);
                        $report[] = array( "order" => $order, "status"=> "success","details"=> $sendRequest['details']);
                    }
                    else{
                        $report[] = array( "order" => $order, "status"=> "error","details"=> $sendRequest['details']);
                    }
                    break;
                

            }
        }catch(Exception $e){
            $error = $e->getMessage();
            $report[] = array("order" => $order, "status" => "error", "details" => $error);
           
        }
        }
        return array("status"=> "success","details"=> $report);
    }
    

    

    
}
