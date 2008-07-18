<?php
/**
 * @author Nikolai Kordulla
 */
class PBBool extends PBMessage
{
    var $wired_type = PBMessage::WIRED_VARINT;

    /**
     * Parses the message for this type
     *
     * @param array
     */
    public function ParseFromArray($array)
    {
        $first = $array[$this->pointer];
        $this->pointer++;

        $number = $this->base128->get_value($first);
		
		if ($number != 0 && $number != 1)
			throw new Exception('Wrong value for boolean');
		
        $this->value = (1 == $number);
        return $this->pointer;
    }

    /**
     * Serializes type
     */
   public function SerializeToString($rec=-1)
   {
        // first byte is length byte
        $string = '';

        if ($rec > -1)
            $string .= $this->base128->set_value($rec << 3 | $this->wired_type);

        $string .= $this->base128->set_value($this->value);
        return $string;
   }
}
?>