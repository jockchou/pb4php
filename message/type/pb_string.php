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
    public function ParseFromArray(&$array)
    {
        $string = '';
        // first byte is length
        $first = array_shift($array);
        $length = $this->base128->get_value($first);

        // now calculate the length
        $newlength = 0;
        for ($i=0; $i < $length; ++$i)
        {
            $newlength += strlen($array[$i]) / 8;
            if ($newlength >= $length)
                break;
        }

        if ($newlength < $length)
        {
            var_dump($array);
            throw new Exception('Length is set to ' . $length . ' but ' . $newlength . ' available');
        }

        for ($i=0; $i < $newlength; ++$i)
        {
            $first = array_shift($array);
            $number = $this->base128->get_value($first);
            $string .= (chr($number));
        }

        $this->value = ($string);
        return $string;
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