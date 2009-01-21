<?php
/**
 * Reads string input
 */
class PBInputStringReader extends PBInputReader
{
	var $length = 0;

	public function __construct($string)
	{
		parent::__construct();
		$this->string = $string;
		$this->length = strlen($string);
	}
	
	public function next()
	{
		$package = '';
		while (true)
		{
			if ($this->pointer >= $this->length)
			{
				return false;
			}
				
	        $value = decbin(ord($this->string[$this->pointer]));
			$this->pointer++;
			
	        if ($value >= 10000000)
	        {
	            // now fill to eight with 00
	            $package .= $value;
	        }
	        else
	        {
	            // now fill to length of eight with 0
	            $value = substr('00000000', 0, 8 - strlen($value) % 8) . $value;
	            return $this->base128->get_value($package . $value);
	        }
        }		
	}
}
?>