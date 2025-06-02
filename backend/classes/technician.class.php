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
  public $address;
  public $scholarship;
  public $enabled;
  public $file_path;

  public function __construct($id, $name, $email, $special_needs, $document_number, $document_emissor, $document_uf, $phone, $cpf,  $created_at, $address, $scholarship, $enabled, $file_path) {
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
    $this->address = $address;
    $this->scholarship = $scholarship;
    $this->enabled = $enabled;
    $this->file_path = $file_path;
  }

}