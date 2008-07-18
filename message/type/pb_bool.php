<?php
/**
 * @author Nikolai Kordulla
 */
class PBBool extends PBInt
{
    var $wired_type = PBMessage::WIRED_VARINT;

    /**
     * Parses the message for this type
     *
     * @param array
     */
    public function ParseFromArray($array)
    {
    	$this->pointer = parent::ParseFromArray($array);

 		if ($this->value != 0 && $this->value != 1)
			throw new Exception('Wrong value for boolean');
		
		var_dump($this->value);
        $this->value = (1 == $this->value);
        return $this->pointer;
    }

}
?>