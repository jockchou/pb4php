<?php
class AddressBook_PhoneType extends PBEnum
{
    const MOBILE = 0;
    const HOME = 1;
    const WORK = 2;
}
class AddressBook extends PBMessage
{
    var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
    public function __construct($reader = null)
    {
        parent::__construct($reader);
        $this->fields[2] = 'PBString';
        $this->values[2] = array();
        $this->fields[3] = 'AddressBook_PhoneType';
        $this->values[3] = array();
    }
    function person()
    {
        $arr = array();
        for ($i = 0; $i < $this->_get_arr_size(2); ++$i)
        {
            $arr[] = $this->_get_arr_value(2, $i)->get_value();
        }
        return $arr;
    }
    function add_person($value)
    {
        $v = new PBString();
        $v->set_value($value);
        $this->values[2][] = $v;
    }
    function set_person($arr_value)
    {
        $this->values[2] = array();
        foreach ($arr_value as $value)
        {
            $v = new PBString();
            $v->set_value($value);
            $this->values[2][] = $v;
        }
    }
    function clear_person()
    {
        $this->values[2] = array();
    }
    function type()
    {
        $arr = array();
        for ($i = 0; $i < $this->_get_arr_size(3); ++$i)
        {
            $arr[] = $this->_get_arr_value(3, $i)->get_value();
        }
        return $arr;
    }
    function add_type($value)
    {
        $v = $this->_add_arr_value(3);
        $v->set_value($value);
    }
    function set_type($arr_value)
    {
        $this->values[3] = array();
        foreach($arr_value as $value)
        {
            $v = $this->_add_arr_value(3);
            $v->set_value($value);
        }
    }
    function clear_type()
    {
        $this->values[3] = array();
    }
}
?>