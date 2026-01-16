<?php
// backend/services/technician.service.php - FIXED VERSION

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/technician.class.php';

class TechnicianService {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTechnician($user_id) {
        // FIXED SQL - using correct column names from user table
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
                u.street,           -- Fixed: was u.address
                u.city,
                u.state,
                u.zip_code,         -- Fixed: was u.zip
                u.number,           -- Added
                u.complement,       -- Added
                u.neighborhood,     -- Added
                u.scholarship,
                u.enabled,
                d.path AS file_path,
                ur.role
            FROM user u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN documents d ON d.user_id = u.id
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

        // Build Address object from user table fields
        $address = null;
        if (!empty($row['street']) || !empty($row['city'])) {
            $address = new Address(
                null,                           // id
                $row['street'] ?? '',           // street
                $row['city'] ?? '',             // city
                $row['state'] ?? '',            // state
                $row['zip_code'] ?? '',         // zip (from zip_code column)
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
            $address,                           // Pass the Address object
            $row['city'],                       // Keep for backward compatibility
            $row['state'],                      // Keep for backward compatibility
            $row['zip_code'],                   // Fixed: was zip
            $row['scholarship'],
            $row['enabled'],
            $row['file_path'],
            $statusText,
            $statusClass
        );
    }

    // Optional: Add other methods as needed
    public function getAllTechnicians() {
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
            WHERE ur.role = 'tecnico'
            ORDER BY u.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTechnicianStatus($user_id, $status) {
        $sql = "UPDATE user SET enabled = :status WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>