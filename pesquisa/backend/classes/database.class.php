<?php

class Database {
  private $driver;
  private $host;
  private $dbname;
  private $username;
  private $conn;

  function __construct() {
    $this->host = '10.243.79.4';
    $this->dbname = 'esesp_pesquisas';
    $this->username = 'root';

  }

  function connect() {
    try {
      $this->conn = new PDO(
        "mysql:host=$this->host;dbname=$this->dbname;charset=utf8", $this->username, ''
      );

      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      return $this->conn;

    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }


}