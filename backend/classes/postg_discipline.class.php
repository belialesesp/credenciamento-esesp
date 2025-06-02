<?php

class DisciplinePostg {
  private $id;
  public $name;
  public $post_graduation;
  public $eixo;

  public function __construct($id, $name, $post_graduation, $eixo) {
    $this->id = $id;
    $this->name = $name;
    $this->post_graduation = $post_graduation;
    $this->eixo = $eixo;
  }

}