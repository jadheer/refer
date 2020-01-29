<?php
class ES_Customer_know extends ESSource
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
	
	public function getKnow($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['published'=>(int)1]]]];
			
			if (!empty($object->geography_id)):
				$params['body']['query']['bool']['filter'] 	= 	[['terms' 	=> 	['geography_id'	=> explode(",",$object->geography_id)]]];
			endif;
			$params['body']['sort']	=	[['pub_datetime' => ['order' => 'desc']]];
			
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		try{
			$arr 		=	[];
			$empIds		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $know_obj):
					if (!empty($know_obj['_source'])):
						$row		=	(object)$know_obj['_source'];
						array_push($empIds,$row->owner_id);
					endif;
				endforeach;
				$empIds			=	array_unique($empIds);
				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
					$ES_Geography	=	new ES_Geography();
					$ObjGeos		=	$ES_Geography->getGeographies();
					$ObjMaster		=	(object)['geographies'	=>	$ObjGeos];
				endif;
			
				foreach ($results['hits']['hits'] as $know_obj):
				if (!empty($know_obj['_source'])):
						$row						=	(object)$know_obj['_source'];
						if($objEmployees[$row->owner_id]['enabled'] == 1):
							$robject					=	new stdClass();
							$robject->id				=	(int)$row->know_id;
							$robject->name				=	(string)$this->common->entityDecode($row->name);
							$robject->description		=	(string)$this->common->entityDecode($row->description);
							$robject->location			=	(string)$this->common->entityDecode($row->location);
							$robject->base_url  		=	(string)_AWS_URL._CLIENTVISITS_IMAGES_DIR;
							$robject->promo_image		=	(string)$row->promo_image;
							$robject->pub_datetime 		=	(string)$row->pub_datetime;
							$robject->geography			=	new stdClass();
							$Key						=	array_search($row->geography_id , array_column($ObjMaster->geographies, 'geography_id'));
							if ($Key!==false):
								$robject->geography		= (object)[	'geography_id'	=> 	(int)$ObjMaster->geographies[$Key]->geography_id,
																	'title' 		=> 	(string)$ObjMaster->geographies[$Key]->title];
							endif;
							$robject->account_owner		=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$row->owner_id]['employee_id'],
																		'first_name'		=>	(string)$objEmployees[$row->owner_id]['first_name'],
																		'middle_name'		=>	(string)$objEmployees[$row->owner_id]['middle_name'],
																		'last_name'			=>	(string)$objEmployees[$row->owner_id]['last_name'],
																		'display_name'		=>	(string)$objEmployees[$row->owner_id]['display_name'],
																		'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																											'image_path'	=>	(string)$objEmployees[$row->owner_id]['profile_picture']]];
							array_push($arr, $robject);
						endif;
					endif;
				endforeach;
			endif;
		return $arr;
		}catch(Exception $e)
		{	$this->es_error($e);	}
}
}?>