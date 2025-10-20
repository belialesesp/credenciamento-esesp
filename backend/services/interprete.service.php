<?php
// backend/services/interprete.service.php - Updated to match TechnicianService structure

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/interpreter.class.php';

class InterpreterService {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getInterpreter($user_id) {
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
                u.street,
                u.city,
                u.state,
                u.zip_code,
                u.number,
                u.complement,
                u.neighborhood,
                u.scholarship,
                u.enabled,
                d.path AS file_path
            FROM user u
            INNER JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN documents d ON d.user_id = u.id
            WHERE u.id = :user_id 
                AND ur.role = 'interprete'
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Create Address object
        $address = null;
        if ($row['street'] || $row['city'] || $row['state']) {
            $address = new Address(
                null,                           // id
                $row['street'] ?? '',           // street
                $row['city'] ?? '',             // city
                $row['state'] ?? '',            // state
                $row['zip_code'] ?? '',         // zip
                $row['complement'] ?? '',       // complement
                $row['number'] ?? '',           // number
                $row['neighborhood'] ?? ''      // neighborhood
            );
        }

        // Map status fields
        $statusText = match($row['enabled']) {
            1 => 'Apto',
            0 => 'Inapto',
            default => 'Aguardando aprovação'
        };

        $statusClass = match($row['enabled']) {
            1 => 'status-approved',
            0 => 'status-not-approved',
            default => 'status-pending'
        };

        // Create Interpreter object with ALL required params
        return new Interpreter(
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
            $row['called_at'],              // Added this field
            $address,                       // Pass the Address object
            $row['scholarship'],
            $row['enabled'],
            $row['file_path'],
            $statusText,                    // Added this field
            $statusClass                    // Added this field
        );
    }

    public function getAllInterpreters() {
        $sql = "
            SELECT 
                u.id,
                u.name,
                u.email,
                u.phone,
                u.cpf,
                u.created_at,
                u.enabled
            FROM user u
            INNER JOIN user_roles ur ON ur.user_id = u.id
            WHERE ur.role = 'interprete'
            ORDER BY u.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateInterpreterStatus($user_id, $status) {
        $sql = "UPDATE user SET enabled = :status WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}