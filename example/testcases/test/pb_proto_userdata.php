<?php
class b extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBString";
    $this->values["1"] = "";
  }
  function query()
  {
    return $this->_get_value("1");
  }
  function set_query($value)
  {
    return $this->_set_value("1", $value);
  }
}
class a extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["9"] = "PBString";
    $this->values["9"] = "";
    $this->fields["1"] = "PBString";
    $this->values["1"] = "";
    $this->fields["4"] = "b";
    $this->values["4"] = array();
  }
  function id()
  {
    return $this->_get_value("9");
  }
  function set_id($value)
  {
    return $this->_set_value("9", $value);
  }
  function clientname()
  {
    return $this->_get_value("1");
  }
  function set_clientname($value)
  {
    return $this->_set_value("1", $value);
  }
  function conf($offset)
  {
    return $this->_get_arr_value("4", $offset);
  }
  function add_conf()
  {
    return $this->_add_arr_value("4");
  }
  function set_conf($index, $value)
  {
    $this->_set_arr_value("4", $index, $value);
  }
  function remove_last_conf()
  {
    $this->_remove_last_arr_value("4");
  }
  function conf_size()
  {
    return $this->_get_arr_size("4");
  }
}
?>