<?php
// backend/classes/postg_discipline.class.php

class DisciplinePostg {
  private $id;
  public $name;
  public $post_graduation;
  public $eixo;
  public $enabled; // Add this property for status

  public function __construct($id, $name, $post_graduation, $eixo, $enabled = null) {
    $this->id = $id;
    $this->name = $name;
    $this->post_graduation = $post_graduation;
    $this->eixo = $eixo;
    $this->enabled = $enabled; // Initialize enabled status
  }

  // Add the missing getId() method
  public function getId() {
    return $this->id;
  }

  // Add status text method
  public function getStatusText() {
    return match ($this->enabled) {
      1 => 'Apto',
      0 => 'Inapto',
      default => 'Aguardando aprovação', 
    };
  }

  // Add status class method for CSS styling
  public function getStatusClass() {
    return match ($this->enabled) {
      1 => 'status-approved',
      0 => 'status-not-approved',
      default => 'status-pending',
    };
  }

  // Add method to update status
  public function updateStatus($status) {
    $this->enabled = $status;
  }
}