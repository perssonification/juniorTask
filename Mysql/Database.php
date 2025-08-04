<?php

declare(strict_types=1);
namespace Mysql;

use PDO;
use PDOException;

class Database
{
    private PDO $conn;

    public function __construct()
    {
        try {
            $this->conn = new PDO(
                'mysql:host=db;dbname=chuck_norris;charset=utf8mb4',
                'root',
                'root'
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('DB Connection failed: ' . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }
}
?>