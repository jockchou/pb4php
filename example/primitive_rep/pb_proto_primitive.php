<?php
class AddressBook extends PBMessage
{
  var $wired_type = PBMessage::WIRED_STRING;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["2"] = "PBString";
    $this->values["2"] = array();
  }
  function person($offset)
  {
    $v = $this->_get_arr_value("2", $offset);
    return $v->get_value();
  }
  function append_person($value)
  {
    $v = $this->_add_arr_value("2");
    $v->set_value($value);
  }
  function person_size()
  {
    return $this->_get_arr_size("2");
  }
}
?>