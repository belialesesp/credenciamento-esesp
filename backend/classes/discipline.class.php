<?php
// backend/classes/discipline.class.php
class Discipline {
  public $id;
  public $name;
  public $eixo;
  public $estacao;
  public $modules = [];
  public $enabled; // Status específico para esta disciplina
  public $called_at;

  function __construct($id, $name, $eixo, $estacao, $modules = [], $enabled = null, $called_at = null) {
    $this->id = $id;
    $this->name = $name;
    $this->eixo = $eixo;
    $this->estacao = $estacao;
    $this->modules = $modules;
    $this->enabled = $enabled;
    $this->called_at = $called_at; // Now properly defined as parameter
  }


  function getId() {
    return $this->id;
  }

  function getStatusText() {
    return match ($this->enabled) {
      1 => 'Apto',
      0 => 'Inapto',
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