<?php

class Lecture {
  private $id;
  public $name;
  public $details;

  function __construct($name, $details) {
    $this->name = $name;
    $this->details = $details;
  }

}