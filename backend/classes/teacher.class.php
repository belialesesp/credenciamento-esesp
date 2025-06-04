<?php
// backend/classes/teacher.class.php

class Teacher {
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
  public $file_path;
  public $disciplines = [];
  public $educations = [];
  public $activities = [];
  public $lectures = [];
  public $enabled;

  function __construct($id, $name, $email, $special_needs, $document_number, $document_emissor, $document_uf, $phone, $cpf, $created_at, $address, $file_path, $disciplines = [], $educations = [], $activities = [], $lectures = [], $enabled = null) {
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
    $this->file_path = $file_path;
    $this->disciplines = $disciplines;
    $this->educations = $educations;
    $this->activities = $activities;
    $this->lectures = $lectures;
    $this->enabled = $enabled;
  }

  function update_status($status) {
    $this->enabled = $status;
  }
}