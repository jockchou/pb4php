<?php
/**
 * @author Nikolai Kordulla
 */
class PBString extends PBMessage
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
            $this->value .= (chr($this->reader->next()));
		}
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

        $string .= $this->base128->set_value(mb_strlen($value));

        for ($i=0; $i < strlen($value); ++$i)
            $string .= $this->base128->set_value(ord($value[$i]));
        return $string;
   }
}
?>