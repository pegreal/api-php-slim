<?php

namespace Services;

class OrdersService
{
    private $dbService;
    public function __construct(DatabaseService $dbService)
    {
        $this->dbService = $dbService;

    }

    public function getOrders($userId)
    {
 
        return 'toDo';
    }

    
}
