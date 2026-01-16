<?php

class Address
{
  private $id;
  public $street;
  public $city;
  public $state;
  public $zip;
  public $complement;
  public $number;
  public $neighborhood;

  public function __construct(
    $id,
    $street = '',
    $city = '',
    $state = '',
    $zip = '',
    $complement = '',
    $number = '',
    $neighborhood = ''
  ) {
    $this->id = $id;
    $this->street = $street ?? '';
    $this->city = $city ?? '';
    $this->state = $state ?? '';
    $this->zip = $zip ?? '';
    $this->complement = $complement ?? '';
    $this->number = $number ?? '';
    $this->neighborhood = $neighborhood ?? '';
  }

  function __tostring()
  {
    if (strlen($this->complement) > 0) {
      return $this->street . ", " . $this->number . ", " . $this->complement . " - " . $this->neighborhood;
    } else {
      return $this->street . ", " . $this->number . " - " . $this->neighborhood;
    }
  }
}
