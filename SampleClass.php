<?php
class SampleClass
{
	private $stringData;
	private $intData;
	private $boolData;
	
	public function __construct($strVal, $intVal, $boolVal)
	{
		$this->stringData  = $strVal;
		$this->intData  = $intVal;
		$this->boolData  = $boolVal;
	}
	
    public function getString()
    {
        return $this->stringData; 
    }

    public function getInt()
    {
        return $this->intData; 
    }
	
	public function getBool()
    {
        return $this->boolData; 
    }
}

?>