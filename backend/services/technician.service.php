<?php

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/technician.class.php';


class TechnicianService {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTechnician($user_id) {
        $sql = "
            SELECT 
                u.id,
                u.name,
                u.email,
                u.special_needs,
                u.document_number,
                u.document_emissor,
                u.document_uf,
                u.phone,
                u.cpf,
                u.created_at,
                u.called_at,
                u.address,
                u.city,
                u.state,
                u.zip,
                u.scholarship,
                u.enabled,
                u.file_path,
                ur.role
            FROM user u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            WHERE u.id = :user_id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null; // not found
        }

        // Build Address object if fields are present
        $address = null;
        if (!empty($row['address']) || !empty($row['city'])) {
            $address = new Address(
                null,                         // address id (optional, if you have one)
                $row['address'],              // street
                $row['city'],
                $row['state'],
                $row['zip'],
                $row['complement'] ?? null,
                $row['number'] ?? null,
                $row['neighborhood'] ?? null
            );
        }

        // Map status fields
        $statusText  = $row['enabled'] ? "Ativo" : "Inativo";
        $statusClass = $row['enabled'] ? "status-enabled" : "status-disabled";

        // Create Technician object with ALL required params
        return new Technician(
            $row['id'],
            $row['name'],
            $row['email'],
            $row['special_needs'],
            $row['document_number'],
            $row['document_emissor'],
            $row['document_uf'],
            $row['phone'],
            $row['cpf'],
            $row['created_at'],
            $row['called_at'],
            $address,
            $row['city'],
            $row['state'],
            $row['zip'],
            $row['scholarship'],
            $row['enabled'],
            $row['file_path'],
            $statusText,
            $statusClass
        );
    }

}
