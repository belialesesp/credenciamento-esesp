<?php

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/technician.class.php';


class TechnicianService {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  function getTechnician($technician_id) {
  $sql = "
    SELECT
      t.name,
      t.email,
      t.special_needs,
      t.document_number,
      t.document_emissor,
      t.document_uf,
      t.phone,
      t.cpf,
      t.scholarship,
      t.address_id,
      t.created_at,
      t.enabled,
      t.called_at,
      a.street,
      a.city,
      a.state,
      a.zip_code,
      a.complement,
      a.address_number,
      a.neighborhood,
      d.path AS file_path
    FROM
      technician AS t
    LEFT JOIN
      address AS a
      ON a.id = t.address_id
    LEFT JOIN
      documents AS d
      ON d.technician_id = t.id
    WHERE 
      t.id = :technician_id   
  ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(":technician_id", $technician_id, PDO::PARAM_INT);
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

    $technician = new Technician(
      $technician_id,
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

    return $technician;

  }

  function updateStatus($technician_id, $status) {
    $sql = "
    UPDATE technician 
      SET enabled = :status
    WHERE id = :id
    ";

    $query_run = $this->db->prepare($sql);

    $data = [
    ':status' => $status,
    ':id' => $technician_id
    ];

    $query_execute = $query_run->execute($data);
  
  }

}