<?php
class ES_Wish extends ESSource{
	var $index;
	var $type;
	public function __construct(){
		parent::__construct();
		$this->index	=	'wishes';
		$this->type		=	'wish';
	}

    public function __destruct()
	{	parent::__destruct();	}


	public function getWishes($object){
		try{

			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;

			$params['body']['query']['bool']	=	['must' => [['term' => ['type'=>(string)$object->type]],
																['term' => ['receiver_id'=>(int)$object->employee_id]]]];
			$params['body']['sort']	= [['sent_on' => ['order' => 'desc']]];
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function setOutput($results){
		try{
			$arr 		=	[];
			if ($results['hits']['total']>0):
				$empIds	=	$this->array_column_recursive($results['hits']['hits'], 'sender_id');
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmps		=	$ES_Employee->getEmployeeByIds($empIds);

					if (!empty($objEmps)):
						foreach ($results['hits']['hits'] as $obj):
								if (!empty($obj['_source'])):
									$row	=	(object)$obj['_source'];
									array_push($arr, $this->setObject($row, $objEmps));
								endif;
						endforeach;
					endif;
				endif;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function setObject($row, $objEmps){
		try{
			$robject 	=	new stdClass();
			$robject->wish_id 	=	(int)$row->wish_id;
			$robject->message	=	(string)$this->common->entityDecode($row->message);
			$robject->sent_on 	=	(string)$row->sent_on;
			$robject->employee	=	(object)[];

			$key	=	array_search($row->sender_id, array_column($objEmps, 'employee_id'));
			if ($key!==false):
				$robject->employee = (object)[	'employee_id' 	 =>	(int)$objEmps[$key]['employee_id'],
												'first_name'	 =>	(string)$this->common->entityDecode($objEmps[$key]['first_name']),
												'middle_name'	 =>	(string)$this->common->entityDecode($objEmps[$key]['middle_name']),
												'last_name'		 =>	(string)$this->common->entityDecode($objEmps[$key]['last_name']),
												'display_name'	 =>	(string)$this->common->entityDecode($objEmps[$key]['display_name']),
												'profile_picture'=> (object)['base_url'	=> (string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																			 'image_path'=> (string)$objEmps[$key]['profile_picture']]];
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function addWish($object){
		try{
			$params = [];
			$params['body']  		= 	[	'wish_id'		=>	(int)$object->wish_id,
											'type'			=>	(string)$object->type,
											'receiver_id'	=>	(int)$object->receiver_id,
											'sender_id'		=>	(int)$object->sender_id,
											'message'		=>	(string)$this->common->entityEncode($object->message),
											'sent_on'		=>	(string)$object->sent_on];

			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->wish_id;
			return $this->es_index($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

    public function refresh(){
		try{
			$params = [];
			$params['index']  		= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function array_column_recursive(array $haystack, $needle) {
		$found = [];
		array_walk_recursive($haystack, function($value, $key) use (&$found, $needle) {
			if (strcmp($key,$needle)==0){
				array_push($found, $value);
			}
		});
		return $found;
	}

	public function getCelebrationMessagesByType($type='BIRTHDAY', $objEmp = NULL) {
      try {
	      $params = [];
				$params['from']  	= 	0;
				$params['size']  	= 	1000;
				$params['index'] 	= 	'celebration_messages';
				$params['scroll']  	= 	'30s';

				$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['type'=>(string)$type]]]];

				$params['body']['sort']						= 	['display_order' => ['order' => 'asc']];

				$results	=	$this->es_search($params);
				$rObjects	=	[];

				while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
					if ($results['hits']['total']>0):
						foreach ($results['hits']['hits'] as $object):
							$row						=		(object)$object['_source'];
							if (!empty($objEmp)) {
								$objDateJoining = new DateTime($objEmp->date_of_joining);
								$objYearsAtMl = $objDateJoining->diff(new DateTime());
								$row->message = str_replace('{ml_years}', (string)(($objYearsAtMl->y <= 1)?$objYearsAtMl->y . ' year':$objYearsAtMl->y.' years'), (string)$row->message);
								$row->message = str_replace('{name}', $objEmp->first_name.' '.$objEmp->middle_name.' '.$objEmp->last_name, (string)$row->message);
							}
							array_push($rObjects,$row->message);
						endforeach;
					endif;
					$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			  }
			return $rObjects;
      } catch (Exception $e)
			{	$this->es_error($e);	}
  }
}
?>
