<?php

class Discipline {
  private $id;
  public $name;
  public $called;
  public $called_at;
  public $enabled;

  function __construct($id, $name, $enabled) {
    $this->id = $id;
    $this->name = $name;
    $this->enabled = $enabled;
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