<?php

namespace Services;
use Exception;

class TemplatesService
{
    private $dbService;
    public function __construct(DatabaseService $dbService)
    {
        $this->dbService = $dbService;

    }

    public function getTemplates($userId = null)
    {
        $action = "SELECT * FROM tblTemplates";
        $templates = $this->dbService->ejecutarConsulta($action);
        return $templates;
    }

    public function updateTemplate($id, $name, $body)
    {
        try{
            $dateUpdate = date('Y-m-d');
            $name = $this->dbService->escape($name);
            $body = $this->dbService->escape($body);
            $action = "UPDATE tblTemplates SET strName= '$name', strBody='$body', updatedAt='$dateUpdate' WHERE tblTemplates.idTemplate = $id";
            $templates = $this->dbService->ejecutarConsulta($action);
            return array("status"=> "success","details"=> $name);
        }catch(Exception $e){
            return array("status"=> "error","details"=> $e->getMessage());
        }
        
    }
    public function createTemplate($name, $body)
    {
        try{
        $dateCreated = date('Y-m-d');
        $name = $this->dbService->escape($name);
        $body = $this->dbService->escape($body);
        $action = "INSERT INTO tblTemplates (strName, strBody, createdAt, updatedAt) VALUES  ( '$name', '$body', '$dateCreated','$dateCreated')";
        $templates = $this->dbService->ejecutarConsulta($action);
        return array("status"=> "success","details"=> $name);
        }catch(Exception $e){
            return array("status"=> "error","details"=> $e->getMessage());
        }
    }

    
}
