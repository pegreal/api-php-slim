<?php

namespace Services;

class InvoicesService
{
    private $dbService;
    private $amazonService;
    private $miraklService;
    private $makroService;
    private $kauflandService;
    private $manomanoService;

    public function __construct(DatabaseService $dbService, AmazonService $amazonService, MiraklService $miraklService, MakroService $makroService, KauflandService $kauflandService, ManomanoService $manomanoService)
    {
        $this->dbService = $dbService;
        $this->amazonService = $amazonService;
        $this->miraklService = $miraklService;
        $this->makroService = $makroService;
        $this->kauflandService = $kauflandService;
        $this->manomanoService = $manomanoService;


    }

    public function getInvoices($market, $limit = 300)
    {
        //$test = $this->amazonService->createInvoiceDocument();
        $action = "SELECT * FROM tblfacturasapp  WHERE idMarket = $market ORDER BY strIdPresta DESC LIMIT $limit ";
        $invoices = $this->dbService->ejecutarConsulta($action);
        $this->dbService->cerrarConexion();
        return $invoices;
    }

    public function updateInvoiceState($idOrderMarket, $market, $state) {
        
        $actionRequest = "UPDATE tblfacturasapp SET intEstado='$state' WHERE tblfacturasapp.strIdmarket = '$idOrderMarket'";

        $this->dbService->ejecutarConsulta($actionRequest);

        return array("status"=> "success","details"=> $idOrderMarket);
        
    }

    public function sendInvoice($market, $idOrderMarket, $country, $invoice){

        if($market && $idOrderMarket){

            switch($market){

                case '1' :
                    $sendInvoice = $this->amazonService->sendInvoice($idOrderMarket, $country, $invoice);
                    if($sendInvoice['status'] == 'success'){
                        $this->updateInvoiceState($idOrderMarket, $market, 2);
                    }
                    return $sendInvoice;
                    //Leroy merlin
                    case '21' :
                    $sendInvoice = $this->miraklService->sendInvoice('leroy',$idOrderMarket, $country, $invoice);
                    if($sendInvoice['status'] == 'success'){
                        $this->updateInvoiceState($idOrderMarket, $market, 2);
                    }
                    return $sendInvoice;
                    //Carrefour
                    case '7' :
                        $sendInvoice = $this->miraklService->sendInvoice('carrefour',$idOrderMarket, $country, $invoice);
                        if($sendInvoice['status'] == 'success'){
                            $this->updateInvoiceState($idOrderMarket, $market, 2);
                        }
                        return $sendInvoice;
                    //Makro
                    case '28' :
                        $sendInvoice = $this->makroService->sendInvoice($idOrderMarket, $country, $invoice);
                        if($sendInvoice['status'] == 'success'){
                            $this->updateInvoiceState($idOrderMarket, $market, 2);
                        }
                        return $sendInvoice;
                    //Kaufland
                    case '33' :
                        $sendInvoice = $this->kauflandService->sendInvoice($idOrderMarket, $country, $invoice);
                        if($sendInvoice['status'] == 'success'){
                            $this->updateInvoiceState($idOrderMarket, $market, 2);
                        }
                        return $sendInvoice;
                    //Manomano
                    case '3' :
                        $sendInvoice = $this->manomanoService->sendInvoice($idOrderMarket, $country, $invoice);
                        if($sendInvoice['status'] == 'success'){
                            $this->updateInvoiceState($idOrderMarket, $market, 2);
                        }
                        return $sendInvoice;
                    
                default :
                    return 'No market defined';
            }
        }
        else{
            return 'Fail';
        }
    }

    public function sincroOrders($markets, $from){

        
        $stringMarkets = $markets; //implode(",", $markets);
        
        $count = 0;
        //TO DO get orders data...
       
        return array('status'=> 'success', 'details' => array('Orders Sincronized:' => $count));

    }

    public function updateBusinessOrders($orders, $market){
        $ordersString = implode("','", $orders);
        $ordersString = "'" . $ordersString . "'"; 
    
        $ordersString = implode('","',$orders);
        $ordersString = '"'.$ordersString.'"';

        $actionRequest = "UPDATE tblfacturasapp SET isBusiness=1 WHERE tblfacturasapp.strIdmarket IN ($ordersString)";

        $this->dbService->ejecutarConsulta($actionRequest);

        return array('status'=> 'success', 'details' => $ordersString);

    }


    public function businessOrders($market, $from)
    {

        if ($market) {

            switch ($market) {

                case '1':

                    $dataBusiness = $this->amazonService->businessOrders($from);

                    return $this->updateBusinessOrders($dataBusiness, '1');
                default:
                    return array('status' => 'error', 'details' => 'No parketplace');
            }
        } else {
            return array('status' => 'error', 'details' => 'No parketplace');
        }
    }



    
}
