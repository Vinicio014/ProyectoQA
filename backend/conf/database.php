<?php
// Evitar redeclaración de clase
if (!class_exists('Database')) {
    class Database {
        private $host = "localhost";
        private $db_name = "Uniformes_deportivos";
        private $username = "root";
        private $password = "Lopez40.";
        private $conn;

        public function getConnection() {
            $this->conn = null;

            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->conn->exec("SET NAMES utf8mb4");
            } catch(PDOException $e) {
                echo "Error de conexión: " . $e->getMessage();
            }

            return $this->conn;
        }
    }
}
?>