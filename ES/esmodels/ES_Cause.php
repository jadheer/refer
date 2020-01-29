<?php 
class ES_Cause extends ESSource{
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function getCauseById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->cause_id;
			$extra	=	(isset($object->extra))?$object->extra:false;

			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$robject	=	$this->setObject((object)$result['_source'], $object, $extra);
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function getCauses($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool'] = ['must' => [['term' => ['enabled'=>1]], ['term' => ['published'=>1]]]];
			
			
			$params['body']['sort']	= [['pub_datetime' => ['order' => 'desc']], ['cause_id' => ['order' => 'desc']]];
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		try{
			$arr 		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						array_push($arr, $this->setObject($row, $object));
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row, $object, $extra=false){
		try{
			$robject 	=	new stdClass();
			$robject->cause_id						=	(int)$row->cause_id;
			$robject->pub_datetime  				= 	(string)$row->pub_datetime;
			$robject->author						=	(string)$this->common->entityDecode($row->author);
			$robject->title							=	(string)$this->common->entityDecode($row->title);
			$robject->summary						=	(string)$this->common->entityDecode($row->summary);
			$robject->youtube_url   				= 	(string)$row->youtube_url;
			$robject->promo_image 					= 	(object)['base_url'		=>	(string)_AWS_URL._CAUSES_IMAGES_DIR,
																'image_path'	=>	(string)$row->promo_image];
			
			$robject->body       					= 	(string)$this->common->entityDecode($row->body);
			$robject->start_date       				= 	(string)$row->start_date;
			$robject->end_date       				= 	(string)$row->end_date;
			$robject->enable_volunteer  			= 	(boolean)(!empty($row->enable_volunteer)?true:false);
			$robject->enable_donation  				= 	(boolean)(!empty($row->enable_donation)?true:false);
			$robject->cause_contribution_option_id  = 	(int)0;
			$robject->donated       				= 	(boolean)in_array($object->employee_id,$row->donors);
			$robject->volunteered       			= 	(boolean)in_array($object->employee_id,$row->volunteers);
			
			if ($extra):
				$robject->volunteers					=	!empty($row->volunteers)?$row->volunteers:[];
				$robject->donors						=	!empty($row->volunteers)?$row->donors:[];
			endif;
			$robject->contribution_options  		= 	[];
			
			$pictures	=	[];
			if (!empty($row->gallery)):
				foreach ($row->gallery as $pic):
					$pObj	=	new stdClass();
					$pObj->picture_path		=	(string)$pic['picture_path'];
					$pObj->picture_caption	=	(string)$pic['picture_caption'];
					array_push($pictures, $pObj);
				endforeach;
			endif;
			$robject->gallary	=	(object)[	'base_url' => (string)_AWS_URL._CAUSES_IMAGES_DIR,
												'pictures' => $pictures];
			
			if (!empty($row->contribution_options)):
				foreach($row->contribution_options as $option):
					$obj   = new stdClass();
					$obj->cause_contribution_option_id  	= 	(int)$option['cause_contribution_option_id'];
					$obj->no_of_days    					= 	(int)$option['no_of_days'];
					$robject->cause_contribution_option_id  = 	(int)($robject->donated)?$option['cause_contribution_option_id']:0;
					array_push($robject->contribution_options, $obj);
				endforeach;
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function volunteer($object){
		try {
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->cause_id;
	
			$params['body']['script']['inline']	=	'ctx._source.volunteers.add(params.volunteers)';
			$params['body']['script']['params']	=	['volunteers' => (int)$object->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function contribute($object){
		try {
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->cause_id;
	
			$params['body']['script']['inline']	=	'ctx._source.donors.add(params.donors)';
			$params['body']['script']['params']	=	['donors' => (int)$object->employee_id];
			return $this->es_update($params);
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
}
?>