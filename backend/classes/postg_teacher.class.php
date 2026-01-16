<?php
// backend/classes/postg_teacher.class.php

require_once 'teacher.class.php';

class TeacherPostG extends Teacher {
  public $post_graduation = [];

  public function __construct($id, $name, $email, $special_needs, $document_number, $document_emissor, $document_uf, $phone, $cpf, $created_at, $address, $file_path, $disciplines = [], $educations = [], $activities = [], $lectures = [], $enabled = null, $post_graduation = []) {
    parent::__construct($id, $name, $email, $special_needs, $document_number, $document_emissor, $document_uf, $phone, $cpf, $created_at, $address, $file_path, $disciplines, $educations, $activities, $lectures, $enabled);
    $this->post_graduation = $post_graduation;
  }
}