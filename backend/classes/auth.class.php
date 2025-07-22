<?php
// backend/classes/auth.class.php - Updated with password management methods
require_once 'database.class.php';

class AuthHelper {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Authenticate user using CPF and password
     * Now uses unified user table
     */
    public function authenticate($cpf, $password) {
        // Clean CPF
        $cleanCpf = preg_replace('/[^0-9a-zA-Z]/', '', $cpf);
        
        try {
            // Special case for admin login
            if ($cpf === 'credenciamento' || $cleanCpf === 'credenciamento') {
                $sql = "SELECT * FROM user WHERE email = 'credenciamento'";
                $stmt = $this->conn->prepare($sql);
            } else {
                // Regular CPF login
                $sql = "SELECT * FROM user WHERE cpf = :cpf";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':cpf', $cleanCpf);
            }
            
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Get profile page based on user type
                $profilePages = [
                    'admin' => 'home.php',
                    'teacher' => 'docente.php',
                    'postg_teacher' => 'docente-pos.php',
                    'interpreter' => 'interprete.php',
                    'technician' => 'tecnico.php'
                ];
                
                return [
                    'user' => $user,
                    'type' => $user['user_type'],
                    'table' => 'user',
                    'profile_page' => $profilePages[$user['user_type']] ?? 'home.php'
                ];
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user password in unified table
     */
    public function updatePassword($userId, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                UPDATE user 
                SET password_hash = :password_hash,
                    first_login = FALSE
                WHERE id = :user_id
            ");
            
            return $stmt->execute([
                ':password_hash' => $passwordHash,
                ':user_id' => $userId
            ]);
            
        } catch (PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user needs to change password (first login)
     */
    public function needsPasswordChange($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT first_login 
                FROM user 
                WHERE id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['first_login'] == 1;
            
        } catch (PDOException $e) {
            error_log("Check first login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by reset token
     */
    public function findUserByResetToken($token) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * 
                FROM user 
                WHERE password_reset_token = :token 
                AND password_reset_expires > NOW()
                LIMIT 1
            ");
            $stmt->execute([':token' => $token]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Find user by token error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set password reset token
     */
    public function setPasswordResetToken($userId, $token, $expires) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE user 
                SET password_reset_token = :token,
                    password_reset_expires = :expires
                WHERE id = :user_id
            ");
            
            return $stmt->execute([
                ':token' => $token,
                ':expires' => $expires,
                ':user_id' => $userId
            ]);
            
        } catch (PDOException $e) {
            error_log("Set reset token error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear password reset token
     */
    public function clearPasswordResetToken($userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE user 
                SET password_reset_token = NULL,
                    password_reset_expires = NULL
                WHERE id = :user_id
            ");
            
            return $stmt->execute([':user_id' => $userId]);
            
        } catch (PDOException $e) {
            error_log("Clear reset token error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin($userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE user 
                SET last_login = NOW()
                WHERE id = :user_id
            ");
            
            return $stmt->execute([':user_id' => $userId]);
            
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by ID from unified table
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, cpf, user_type, type_id, first_login
                FROM user 
                WHERE id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return false;
        }
    }
}