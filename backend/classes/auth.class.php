<?php
// backend/classes/auth.class.php
require_once 'database.class.php';

class AuthHelper {
    private $conn;
    
    // Map of user types to database tables
    private $userTables = [
        'teacher' => 'teacher',
        'postg_teacher' => 'postg_teacher',
        'interpreter' => 'interpreter',
        'technician' => 'technician'
    ];
    
    // Map of user types to profile pages
    private $profilePages = [
        'teacher' => 'docente.php',
        'postg_teacher' => 'docente-pos.php',
        'interpreter' => 'interprete.php',
        'technician' => 'tecnico.php'
    ];
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Find user across all tables by CPF
     * Returns array with user data and table type, or false if not found
     */
    public function findUserByCPF($cpf) {
        $cleanCpf = preg_replace('/\D/', '', $cpf);
        
        foreach ($this->userTables as $type => $table) {
            try {
                $stmt = $this->conn->prepare("
                    SELECT id, name, email, cpf, password_hash, first_login 
                    FROM $table 
                    WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
                    LIMIT 1
                ");
                
                $stmt->bindParam(':cpf', $cleanCpf);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    return [
                        'user' => $user,
                        'type' => $type,
                        'table' => $table
                    ];
                }
            } catch (PDOException $e) {
                error_log("Error searching in table $table: " . $e->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * Find user by reset token
     */
    public function findUserByResetToken($token, $userType = null) {
        $tables = $userType && isset($this->userTables[$userType]) 
            ? [$userType => $this->userTables[$userType]] 
            : $this->userTables;
        
        foreach ($tables as $type => $table) {
            try {
                $stmt = $this->conn->prepare("
                    SELECT id, name, email, cpf 
                    FROM $table 
                    WHERE password_reset_token = :token 
                    AND password_reset_expires > NOW()
                    LIMIT 1
                ");
                
                $stmt->bindParam(':token', $token);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    return [
                        'user' => $user,
                        'type' => $type,
                        'table' => $table
                    ];
                }
            } catch (PDOException $e) {
                error_log("Error searching reset token in table $table: " . $e->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * Authenticate user with CPF and password
     */
    public function authenticate($cpf, $password) {
        $userData = $this->findUserByCPF($cpf);
        
        if (!$userData) {
            return false;
        }
        
        $user = $userData['user'];
        
        // If no password hash exists, check if password equals CPF (first login)
        if (empty($user['password_hash'])) {
            $cleanCpf = preg_replace('/\D/', '', $cpf);
            if ($password === $cleanCpf) {
                // Set initial password
                $this->updatePassword($userData['table'], $user['id'], $password);
                $user['first_login'] = true;
            } else {
                return false;
            }
        } else {
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return false;
            }
        }
        
        // Update last login
        $this->updateLastLogin($userData['table'], $user['id']);
        
        return [
            'user' => $user,
            'type' => $userData['type'],
            'table' => $userData['table'],
            'profile_page' => $this->profilePages[$userData['type']]
        ];
    }
    
    /**
     * Update user password
     */
    public function updatePassword($table, $userId, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                UPDATE $table 
                SET password_hash = :password,
                    first_login = FALSE,
                    password_reset_token = NULL,
                    password_reset_expires = NULL
                WHERE id = :id
            ");
            
            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':id', $userId);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin($table, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE $table 
                SET last_login = NOW()
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
    }
    
    /**
     * Set password reset token
     */
    public function setResetToken($table, $userId, $token, $expires) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE $table 
                SET password_reset_token = :token,
                    password_reset_expires = :expires
                WHERE id = :id
            ");
            
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expires', $expires);
            $stmt->bindParam(':id', $userId);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error setting reset token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if password meets requirements
     */
    public static function validatePasswordStrength($password) {
        if (strlen($password) < 8) {
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[@$!%*?&]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get user type display name in Portuguese
     */
    public static function getUserTypeDisplayName($type) {
        $names = [
            'teacher' => 'Docente',
            'postg_teacher' => 'Docente Pós-Graduação',
            'interpreter' => 'Intérprete',
            'technician' => 'Técnico'
        ];
        
        return $names[$type] ?? 'Usuário';
    }
}