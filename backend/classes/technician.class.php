<?php

class Technician {
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
    public $called_at;       
    public $address;
    public $city;            
    public $state;           
    public $zip;             
    public $scholarship;
    public $enabled;
    public $file_path;
    public $statusText;      
    public $statusClass;     

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
        $city,
        $state,
        $zip,
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
        $this->city = $city;
        $this->state = $state;
        $this->zip = $zip;
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
