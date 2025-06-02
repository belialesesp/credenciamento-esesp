<?php

class Education {
  private $id;
  public $name;
  public $degree;
  public $institution;

  function __construct($id, $name, $degree, $institution) {
    $this->id = $id;
    $this->name = $name;
    $this->degree = $degree;
    $this->institution = $institution;
  }

}