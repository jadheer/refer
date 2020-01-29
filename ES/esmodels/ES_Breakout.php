<?php 
class ES_Breakout  extends ESSource {
	var $index;
	var $type;
	
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
    public function __destruct()
	{	parent::__destruct();	}

	
	public function getBreakouts($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must'] 	= 	[['term' => ['enabled'=>1]]];
			$params['body']['sort']						=	[['start_datetime' => ['order'	=>	'desc']]];
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		try{
			$objects		=	[];  
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					$row						=	(object)$obj['_source'];
					$robject					=	new stdClass();
					$robject->breakout_id    	= 	(int)$row->breakout_id;
					$robject->title				= 	(string)$this->common->entityDecode($row->title);
					$robject->start_date		= 	(string)date('Y-m-d', strtotime($row->start_datetime));
					$robject->end_date			= 	(string)date('Y-m-d', strtotime($row->end_datetime));
					$robject->approved			=	(boolean)false;
					$robject->promo_image    	= 	(object)[ 	'base_url' 		=>  (string)_AWS_URL._BREAKOUTS_IMAGES_DIR,
																'image_path' 	=>  (string)$row->promo_image];
					$robject->rejected			=	(boolean)false;
					$robject->is_member			=	(boolean)false;
					$members					=	array_column($row->members,NULL,'employee_id');
					if (isset($members[$object->employee_id])):
						$member					=	(object)$members[$object->employee_id];
						$robject->is_member		=	(boolean)(!empty($member->member_id)?true:false);
						$robject->approved		=	(boolean)(!empty($member->approved)?true:false);
						$robject->rejected		=	(boolean)(!empty($member->rejected)?true:false);
					endif;
					array_push($objects, $robject);
				endforeach;
			endif;
			return $objects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function addMember($object){
		try{
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->breakout_id;
			
			$doc 			= 	(object)[	'member_id'		=>	(int)$object->member_id,
											'employee_id'	=>	(int)$object->employee_id,
											'approved'		=>	(int)0,
											'rejected'		=>	(int)0,
											'employee_id'	=>	(int)$object->employee_id,
											'joined_on'		=>	(string)$object->joined_on,
											'enabled'		=>	(int)1];
			
			$params['body']['script']['inline']	=	'ctx._source.members.add(params.member)';
			$params['body']['script']['params']	=	['member' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function addDiscussion($object){
		try{
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->breakout_id;
			
			$doc 			= 	(object)[	'discussion_id'		=>	(int)$object->discussion_id,
											'employee_id'		=>	(int)$object->employee_id,
											'description'		=>	(string)$this->common->entityEncode($object->description),
											'created_on'		=>	(string)$object->created_on,
											'enabled'			=>	(int)1];
			
			$params['body']['script']['inline']	=	'ctx._source.discussions.add(params.discussion)';
			$params['body']['script']['params']	=	['discussion' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function addGalleryPicture($object){
		try{
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->breakout_id;
			
			$doc 			= 	(object)[	'picture_id'		=>	(int)$object->picture_id,
											'employee_id'		=>	(int)$object->employee_id,
											'picture_path'		=>	(string)$object->picture_path,
											'created_on'		=>	(string)$object->created_on,
											'approved'			=>	(int)0,
											'rejected'			=>	(int)0,
											'enabled'			=>	(int)1 ];
			
			$params['body']['script']['inline']	=	'ctx._source.gallery.add(params.gallery)';
			$params['body']['script']['params']	=	['gallery' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getGallery($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['body']['query']['bool']['must']= 	[	[['term' 	=> ["breakout_id" => (int)$object->breakout_id]]],
															 ['nested'	=> ['path' => 'gallery',
																			'query'	=> ['bool'	 => ['must' => [['term' => ['gallery.enabled' => (int)1]],['term' => ['gallery.approved' => (int)1]],['term' => ['gallery.rejected' => (int)0]]]]],
															 				'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	50000 , 'sort'=>['gallery.created_on' => ['order' => 'desc']]]]]];
			$params['body']['sort']	=	[['gallery.created_on' => ['order' => 'desc']]];
		
			$results	=	$this->es_search($params);
			$rObjects			=	[];
			if (isset($results['hits']['hits'][0]['inner_hits']['gallery']['hits']['hits'])):
				$count 	=	$results['hits']['hits'][0]['inner_hits']['gallery']['hits']['total'];
				if ($count>0):
					$docs	=	$results['hits']['hits'][0]['inner_hits']['gallery']['hits']['hits'];
					foreach ($docs as $obj):
						$obj						=	(object)$obj['_source'];
						$date						=	date('Y-m-d',strtotime($obj->created_on));
						$rObjects["date"][$date][]	=	(string)$obj->picture_path;
					endforeach;
					$rObjects["count"]				=	$count;
				endif;
			endif;
			return $rObjects;
		}
		catch(Exception $e)
		{ $this->es_error($e);   }
	}
	
	public function	checkMembership($object){
		try{
			$params = [];
			$params['index'] 		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['_source']  	= 	'breakout_id,title';
			
			$params['body']['query']['bool']['must'] 	= 	[['term'=>	['breakout_id' => $object->breakout_id]],
															 ['nested'	=>	[   'path' 		    => 'members',
																				'query'		    =>  ['bool' => ['must' => [['term' => ['members.employee_id' => (int)$object->employee_id]]]]],
																				'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)1]]]];
			
			$result					=	$this->es_search($params);
			if ( $result['hits']['total']>0 && isset($result['hits']['hits'][0]['inner_hits']['members']['hits']['hits'][0]['_source'])):
				$robject					=	new stdClass();
				$robject					=	(object)$result['hits']['hits'][0]['inner_hits']['members']['hits']['hits'][0]['_source']; 
				$robject->breakout_id		=	$result['hits']['hits'][0]['_source']['breakout_id']??0;
				$robject->title				=	(string)$result['hits']['hits'][0]['_source']['title']?$this->common->entityDecode($result['hits']['hits'][0]['_source']['title']):'';
			endif;
			return !empty($robject)?$robject:[];										 
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getDiscussions($object){
		try{
			$params 			= 	[];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['_source']	=	false;
			
			$params['body']['query']['bool']['must']	= 	[	['term' 	=> ['breakout_id' => (int)$object->breakout_id]],
																['nested'	=>	['path' => 'discussions',
																				 'query'	=> ['bool' => ['must' => [['term' => ['discussions.enabled' => (int)1]]]]],
																				 'inner_hits'	=>	['from'	=>	(int)$object->start, 'size'	=>	(int)$object->end, 'sort' => ['discussions.created_on' => ['order'	=>	'desc' ]]]]]];
					
			$results	=	$this->es_search($params);
			$robjects	=	[];
			$empIds		=	[];
			
			if (isset($results['hits']['hits'][0]['inner_hits']['discussions']['hits']['hits'])):
				if ($results['hits']['hits'][0]['inner_hits']['discussions']['hits']['total']>0):
					$docs	=	$results['hits']['hits'][0]['inner_hits']['discussions']['hits']['hits'];
					foreach ($docs as $obj):
						$obj						=	(object)$obj['_source'];
						$object						=	new stdClass();
						$object->discussion_id		=	(int)$obj->discussion_id;
						$object->description		=	(string)$this->common->entityDecode($obj->description);
						$object->created_on			=	(string)$obj->created_on;
						$object->employee			=	(object)['employee_id' 	=> 	(int)$obj->employee_id];
						
						array_push($empIds, (string)$obj->employee_id);
						array_push($robjects, $object);
					endforeach;
				endif;
			endif;
			
			$posts		=	[];
			$empIds		=	array_unique($empIds);
			if (!empty($empIds)):
				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	$ES_Employee->getEmployeeByIds($empIds);
				
				if (!empty($objEmployees)):
					foreach ($robjects as $robject):
						$Key	=	array_search($robject->employee->employee_id, array_column($objEmployees, 'employee_id'));
						if ($Key!==false):
							$robject->employee		=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$Key]['employee_id'],
																	'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['first_name']),
																	'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['middle_name']),
																	'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$Key]['last_name']),
																	'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['display_name']),
																	'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																										'image_path'	=>	(string)$objEmployees[$Key]['profile_picture']]];
							array_push($posts,$robject);
						endif;
					endforeach;
				endif;
			endif;
			return $posts;
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
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