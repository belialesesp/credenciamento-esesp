<?php

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/interpreter.class.php';


class InterpreterService {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  function getInterpreter($interpreter_id) {
    $sql = "
      SELECT
        i.name,
        i.email,
        i.special_needs,
        i.document_number,
        i.document_emissor,
        i.document_uf,
        i.phone,
        i.cpf,
        i.scholarship,
        i.enabled,
        i.address_id,
        i.created_at,
        a.street,
        a.city,
        a.state,
        a.zip_code,
        a.complement,
        a.address_number,
        a.neighborhood,
        d.path AS file_path
      FROM
        interpreter AS i
      LEFT JOIN
        address AS a
        ON a.id = i.address_id
      LEFT JOIN
        documents AS d
        ON d.interpreter_id = i.id
      WHERE 
        i.id = :interpreter_id   
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(":interpreter_id", $interpreter_id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if($result) {
      $address = new Address(
        $result["address_id"],
        $result["street"],
        $result["city"],
        $result["state"],
        $result["zip_code"],
        $result["complement"],
        $result["address_number"],
        $result["neighborhood"],
      );

    }

    $interpreter = new Interpreter(
      $interpreter_id,
      $result["name"],
      $result["email"],
      $result["special_needs"],
      $result["document_number"],
      $result["document_emissor"],
      $result["document_uf"],
      $result["phone"],
      $result["cpf"],
      $result['created_at'],
      $address,
      $result['scholarship'],
      $result['enabled'],
      $result['file_path']
    );

    return $interpreter;

  }

  function updateStatus($interpreter_id, $status) {
    $sql = "
    UPDATE interpreter 
      SET enabled = :status
    WHERE id = :id
    ";

    $query_run = $this->db->prepare($sql);

    $data = [
    ':status' => $status,
    ':id' => $interpreter_id
    ];

    $query_execute = $query_run->execute($data);
  
  }

}