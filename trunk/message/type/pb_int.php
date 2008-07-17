<?php
/**
 * @author Nikolai Kordulla
 */
class PBInt extends PBMessage
{
    var $wired_type = PBMessage::WIRED_VARINT;

    /**
     * Parses the message for this type
     *
     * @param array
     */
    public function ParseFromArray(&$array)
    {
        $number = $this->base128->get_value($array[0]);
        $array = array_slice($array, 1, count($array));

        $this->value = $number;
        return $number;
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