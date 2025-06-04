<?php
// backend/classes/discipline.class.php

class Discipline {
  private $id;
  public $name;
  public $eixo;
  public $estacao;
  public $modules = [];
  public $enabled; // Status específico para esta disciplina

  function __construct($id, $name, $eixo, $estacao, $modules = [], $enabled = null) {
    $this->id = $id;
    $this->name = $name;
    $this->eixo = $eixo;
    $this->estacao = $estacao;
    $this->modules = $modules;
    $this->enabled = $enabled;
  }

  function getId() {
    return $this->id;
  }

  function getStatusText() {
    return match ($this->enabled) {
      1 => 'Apto',
      0 => 'Não apto',
      default => 'Aguardando aprovação', 
    };
  }

  function getStatusClass() {
    return match ($this->enabled) {
      1 => 'status-approved',
      0 => 'status-not-approved',
      default => 'status-pending',
    };
  }

  // Legacy methods if needed
  function call_teacher() {
    $this->called = true;
    $this->called_at = date("Y-m-d H:i:s");
  }

  function uncall_teacher() {
    $this->called = false;
  }

  function update_status($status) {
    $this->enabled = $status;
  }
}