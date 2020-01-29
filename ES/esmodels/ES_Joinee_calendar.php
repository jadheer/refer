<?php
class ES_Joinee_calendar extends ESSource
{
	var $index;
	var $type;
	public function __construct()
	{
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	
	public function getCalendars($object){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['geography_id'=>(int)$object->geography->geography_id]],
																['range'=> ['end_datetime' => [ 'gte' => (string)date('Y-m-d H:i:s'),"format"=> "yyyy-MM-dd HH:mm:ss"]]]]];
			
			$params['body']['sort']				=	[	['start_datetime'	=>	['order'	=>	'asc']],
														['end_datetime'		=>	['order'	=>	'asc']]];
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		$arr 		=	[];
		if ($results['hits']['total']>0):
			foreach ($results['hits']['hits'] as $calendar_obj):
				if (!empty($calendar_obj['_source'])):
					$row					=	(object)$calendar_obj['_source'];
					$robject				=	new stdClass();
					$month					=	(string)date('F-Y', strtotime($row->start_datetime));
					$robject->calendar_id 	=	(int)$row->calendar_id;
					$robject->start_date	=	(object)[	'day'	=>	(string)date('D', strtotime($row->start_datetime)),
															'date'	=>	(string)date('Y-m-d H:i:s', strtotime($row->start_datetime))];
					$robject->end_date 		=	(object)[	'day'	=>	(string)date('D', strtotime($row->end_datetime)),
															'date'	=>	(string)date('Y-m-d H:i:s', strtotime($row->end_datetime))];
					$robject->applied		=	(in_array($object->employee_id,$row->applicants))?true:false;
					$arr[$month][]			=	$robject;
				endif;
			endforeach;
		endif;
		return $arr;
	}
	
	public function addEmployeeCalendar($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->calendar_id;
			
			$params['body']['script']['inline']	=	'ctx._source.applicants.add(params.apply)';
			$params['body']['script']['params']	=	['apply' => (int)$object->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function deleteEmployeeCalendar($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->calendar_id;
			
			$params['body']['script']['inline']	=	"ctx._source.applicants.removeAll(Collections.singleton(params.apply))";
			$params['body']['script']['params']	=	['apply' => (int)$object->employee_id];
			
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function refresh(){
		try
		{
			$params = [];
			$params['index']  		= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>