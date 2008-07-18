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
    public function ParseFromArray($array)
    {
        $string = '';
        // first byte is length
        $first = $array[$this->pointer];
        $this->pointer++;
        $length = $this->base128->get_value($first);

        // now calculate the length
        $newlength = 0;

        while (true && !empty($array))
        {
            $first = $array[$this->pointer];
            $this->pointer++;
            $newlength += strlen($first) / 8;

            $number = $this->base128->get_value($first);
            $string .= (chr($number));

            if ($newlength >= $length)
                break;
        }

        if ($newlength < $length)
            throw new Exception('Length is set to ' . $length . ' but ' . $newlength . ' available');

        $this->value = ($string);
        return $this->pointer;
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