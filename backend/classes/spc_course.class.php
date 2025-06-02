<?php

class SpcCourse {
  private $id;
  public $name;
  public $institution;

  public function __construct($id, $name, $institution) {
    $this->id = $id;
    $this->name = $name;
    $this->institution = $institution;
  }

}