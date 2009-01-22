<?php
/**
 * @author Nikolai Kordulla
 */
class PBString extends PBScalar
{
    var $wired_type = PBMessage::WIRED_STRING;

    /**
     * Parses the message for this type
     *
     * @param array
     */
    public function ParseFromArray()
    {
        $this->value = '';
        // first byte is length
        $length = $this->reader->next();
		
		for ($i=0; $i < $length; ++$i)
		{
            $this->value .= (chr($this->reader->next(true)));
		}
		
		// perhaps if iso saved then try to encode
		//$this->value = mb_convert_encoding($this->value, "UTF-8");
    }

    /**
     * Serializes type
     */
   public function SerializeToString($rec = -1)
   {
        $string = '';

        if ($rec > -1)
            $string .= $this->base128->set_value($rec << 3 | $this->wired_type);
		
        // now the string
        $value = ($this->value);
        
        $add = mb_convert_encoding($this->value, "UTF-8");
        $string .= $this->base128->set_value(strlen($add));
		$string .= $add;
		
        return $string;
   }
}
?>