<?php
// backend/classes/postg_discipline.class.php - UPDATED WITH ACTIVITIES PROPERTY

class DisciplinePostg {
  private $id;
  public $name;
  public $post_graduation;
  public $eixo;
  public $enabled;
  public $called_at;
  public $gese_evaluation;
  public $gese_evaluated_at;
  public $gese_evaluated_by;
  public $pedagogico_evaluation;
  public $pedagogico_evaluated_at;
  public $pedagogico_evaluated_by;
  public $activities = []; // NEW: Activities array for this discipline

  public function __construct(
    $id, 
    $name, 
    $post_graduation, 
    $eixo, 
    $enabled = null, 
    $called_at = null,
    $gese_evaluation = null,
    $gese_evaluated_at = null,
    $gese_evaluated_by = null,
    $pedagogico_evaluation = null,
    $pedagogico_evaluated_at = null,
    $pedagogico_evaluated_by = null
  ) {
    $this->id = $id;
    $this->name = $name;
    $this->post_graduation = $post_graduation;
    $this->eixo = $eixo;
    $this->enabled = $enabled;
    $this->called_at = $called_at;
    $this->gese_evaluation = $gese_evaluation;
    $this->gese_evaluated_at = $gese_evaluated_at;
    $this->gese_evaluated_by = $gese_evaluated_by;
    $this->pedagogico_evaluation = $pedagogico_evaluation;
    $this->pedagogico_evaluated_at = $pedagogico_evaluated_at;
    $this->pedagogico_evaluated_by = $pedagogico_evaluated_by;
  }

  public function getId() {
    return $this->id;
  }

  public function getName() {
    return $this->name;
  }

  public function getEixo() {
    return $this->eixo;
  }

  public function getStatusText() {
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

  public function getStatusClass() {
    if ($this->gese_evaluation === null || $this->pedagogico_evaluation === null) {
      return 'status-pending';
    }
    if ($this->gese_evaluation === 1 && $this->pedagogico_evaluation === 1) {
      return 'status-approved';
    }
    return 'status-not-approved';
  }

  public function updateStatus($status) {
    $this->enabled = $status;
  }
}