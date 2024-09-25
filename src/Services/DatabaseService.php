<?php

namespace Services;

class DatabaseService
{
    private $db;

     public function __construct($host, $dbname, $usuario, $contrasena)
    {
        $this->db = new \mysqli($host, $usuario, $contrasena, $dbname);
        $this->db->set_charset("utf8");
        if ($this->db->connect_error) {
            die('Error de conexión a la base de datos: ' . $this->db->connect_error);
        }
    }
    public function cerrarConexion()
    {
        if ($this->db) {
            $this->db->close();
        }
    }

     public function ejecutarConsulta($sql)
    {
        $result = $this->db->query($sql);

        if (!$result) {
            die('Error en la consulta: ' . $this->db->error);
        }

         // Si es una consulta de inserción y la tabla tiene una columna autoincremental
        if (strpos(strtoupper($sql), 'INSERT') !== false && $this->db->insert_id !== 0) {
            // Devolver el valor de la clave primaria insertada
            return $this->db->insert_id;
        }
         // Si es una consulta de actualización
        if (strpos(strtoupper($sql), 'UPDATE') !== false || strpos(strtoupper($sql), 'DELETE') !== false) {
            // Obtener información sobre la actualización (número de filas afectadas, etc.)
            $updateInfo = $this->db->affected_rows;

            return $updateInfo;
        }

        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }

}