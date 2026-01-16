<?php
// backend/classes/interpreter.class.php - Updated to match Technician class structure

class Interpreter {
    private $id;
    public $name;
    public $email;
    public $special_needs;
    public $document_number;
    public $document_emissor;
    public $document_uf;
    public $phone;
    public $cpf;
    public $created_at;
    public $called_at;          // Added field
    public $address;
    public $city;               // Keep for backward compatibility
    public $state;              // Keep for backward compatibility
    public $zip;                // Keep for backward compatibility
    public $scholarship;
    public $enabled;
    public $file_path;
    public $statusText;         // Added field
    public $statusClass;        // Added field

    public function __construct(
        $id,
        $name,
        $email,
        $special_needs,
        $document_number,
        $document_emissor,
        $document_uf,
        $phone,
        $cpf,
        $created_at,
        $called_at,
        $address,
        $scholarship,
        $enabled,
        $file_path,
        $statusText = null,
        $statusClass = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->special_needs = $special_needs;
        $this->document_number = $document_number;
        $this->document_emissor = $document_emissor;
        $this->document_uf = $document_uf;
        $this->phone = $phone;
        $this->cpf = $cpf;
        $this->created_at = $created_at;
        $this->called_at = $called_at;
        $this->address = $address;
        
        // Extract city, state, zip from address for backward compatibility
        if ($address) {
            $this->city = $address->city ?? '';
            $this->state = $address->state ?? '';
            $this->zip = $address->zip ?? '';
        } else {
            $this->city = '';
            $this->state = '';
            $this->zip = '';
        }
        
        $this->scholarship = $scholarship;
        $this->enabled = $enabled;
        $this->file_path = $file_path;
        $this->statusText = $statusText;
        $this->statusClass = $statusClass;
    }

    public function getId() {
        return $this->id;
    }
}