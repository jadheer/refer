<?php
class ES_Currency extends ESSource
{
	var $index;
	var $type;
	public function __construct(){
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
    public function __destruct()
	{	parent::__destruct();	}

	public function getCurrencies(){
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>1]]]];

			return $this->setOutput($this->es_search($params),false);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

    private function setOutput($results,$partial=false){
        try{
            $arr    = [];
            if ($results['hits']['total']>0):
                foreach ($results['hits']['hits'] as $obj):
                    if (!empty($obj['_source'])):
                        $row  = (object)$obj['_source'];
                        array_push($arr, $this->setObject($row, $partial));
                    endif;
                endforeach;
            endif;
            return $arr;
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    private function setObject($row, $partial=false){
        try{
            $robject                   =   new stdClass();
            $robject->currency_id      =   (int)$row->currency_id;
            $robject->name             =   (string)$row->name;
            $robject->abbreviation     =   (string)$row->abbreviation;

            return $robject;
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    public function getCurrencyById($object){
        try{
            $params = [];
            $params['index']    =   $this->index;
            $params['type']     =   $this->type;
            $params['id']       =   (int)$object->currency_id;

            $result =   $this->es_get($params);
            if (!empty($result['_source'])):
                $row                        =   (object)$result['_source'];
                $robject                    =   new stdClass();
                $robject->currency_id       =   (int)$row->currency_id;
                $robject->abbreviation      =   (string)$row->abbreviation;
                $robject->name              =   (string)$row->name;
            endif;
            return !empty($robject)?$robject:new stdClass();
        }
        catch(Exception $e)
        {  $this->es_error($e); }
    }

}
?>
