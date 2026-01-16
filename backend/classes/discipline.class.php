<?php
// backend/classes/discipline.class.php

class Discipline {
  public $id;
  public $name;
  public $eixo;
  public $estacao;
  public $modules = [];
  public $enabled; // Final status
  public $called_at;
  public $gese_evaluation; // GESE evaluation status
  public $gese_evaluated_at;
  public $gese_evaluated_by;
  public $pedagogico_evaluation; // Pedagogico evaluation status
  public $pedagogico_evaluated_at;
  public $pedagogico_evaluated_by;
  public $activities = []; // NEW: Activities array for this discipline

  function __construct($id, $name, $eixo, $estacao, $modules = [], $enabled = null, $called_at = null,
                       $gese_evaluation = null, $gese_evaluated_at = null, $gese_evaluated_by = null,
                       $pedagogico_evaluation = null, $pedagogico_evaluated_at = null, $pedagogico_evaluated_by = null) {
    $this->id = $id;
    $this->name = $name;
    $this->eixo = $eixo;
    $this->estacao = $estacao;
    $this->modules = $modules;
    $this->enabled = $enabled;
    $this->called_at = $called_at;
    $this->gese_evaluation = $gese_evaluation;
    $this->gese_evaluated_at = $gese_evaluated_at;
    $this->gese_evaluated_by = $gese_evaluated_by;
    $this->pedagogico_evaluation = $pedagogico_evaluation;
    $this->pedagogico_evaluated_at = $pedagogico_evaluated_at;
    $this->pedagogico_evaluated_by = $pedagogico_evaluated_by;
  }

  function getId() {
    return $this->id;
  }

  function getName() {
    return $this->name;
  }

  function getEixo() {
    return $this->eixo;
  }

  function getEstacao() {
    return $this->estacao;
  }

  function getModules() {
    return $this->modules;
  }

  function getEnabled() {
    return $this->enabled;
  }

  function getStatusText() {
    if ($this->gese_evaluation === null || $this->pedagogico_evaluation === null) {
      return 'Aguardando avaliações';
    }
    if ($this->gese_evaluation === 1 && $this->pedagogico_evaluation === 1) {
      return 'Apto';
    }
    if ($this->gese_evaluation === 0 || $this->pedagogico_evaluation === 0) {
      return 'Inapto';
    }
    return 'Em avaliação';
  }

  function getStatusClass() {
    if ($this->gese_evaluation === null || $this->pedagogico_evaluation === null) {
      return 'status-pending';
    }
    if ($this->gese_evaluation === 1 && $this->pedagogico_evaluation === 1) {
      return 'status-approved';
    }
    return 'status-not-approved';
  }
  
  function getEvaluationSummary() {
    $summary = [];
    
    if ($this->gese_evaluation !== null) {
      $summary['gese'] = [
        'status' => $this->gese_evaluation,
        'date' => $this->gese_evaluated_at,
        'by' => $this->gese_evaluated_by
      ];
    }
    
    if ($this->pedagogico_evaluation !== null) {
      $summary['pedagogico'] = [
        'status' => $this->pedagogico_evaluation,
        'date' => $this->pedagogico_evaluated_at,
        'by' => $this->pedagogico_evaluated_by
      ];
    }
    
    return $summary;
  }
}