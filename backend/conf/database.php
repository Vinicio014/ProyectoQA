<?php
//Conexion base de datos
class Database {
    private $host = 'localhost';
    private $db_name = 'Uniformes_deportivos'; //nombre BD
    private $username = 'root'; //usuario de MySQL
    private $password = ''; //Password
    private $conn;

    public function getConnection() {
        $this->conn = null;
        //pueba de conexion
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>