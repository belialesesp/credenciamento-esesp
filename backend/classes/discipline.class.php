<?php

class Discipline {
  private $id;
  public $name;
  public $eixo;
  public $estacao;
  public $modules = [];



  function __construct($id, $name, $eixo, $estacao, $modules = []) {
    $this->id = $id;
    $this->name = $name;
    $this->eixo = $eixo;
    $this->estacao = $estacao;
    $this->modules = $modules;
  }

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