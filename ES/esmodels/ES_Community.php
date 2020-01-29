<?php
class ES_Community  extends ESSource
{
	var $index;
	var $type;
	public function __construct()
	{
		parent::__construct();
		$this->index	=	"communities";
		$this->type		=	"community";
	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	public function getCommunityCategories(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	"community_categories";
			$params['type']		=	"category";
			
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]]];
			$params['body']['sort']						= 	[['catagory_order' => ['order' => 'asc']], ['title' => ['order' => 	'asc']], ['category_id' => ['order' => 'asc']]];
			
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row		=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->category_id		=	(int)$row->category_id;
						$robject->title				=	(string)$this->common->entityDecode($row->title);
						$robject->promo_image		=	(object)[	'base_url'		=>	(string)_AWS_URL._COMMUNITIES_IMAGES_DIR,
																	'image_path'	=>	(string)$row->promo_image];
						array_push($results, $robject);
					endif;
				endforeach;
			endif;
			return $results;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}	

	
	public function getCategories(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	"community_categories";
			$params['type']		=	"category";
			
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]]];
			$params['body']['sort']						= 	[['catagory_order' => ['order' => 'asc']], ['title' => ['order' 	=> 	'asc']], ['category_id' => ['order' => 'asc']]];
			
			$objects	=	$this->es_search($params);
			$arr 		= (object)['ids'=>[], 'categories'=>[]];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row		=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->category_id		=	(int)$row->category_id;
						$robject->title				=	(string)$this->common->entityDecode($row->title);
						$robject->communities		=	[];
						array_push($arr->ids, $robject->category_id);
						array_push($arr->categories, $robject);
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	

	
	public function getCommunityCategoryById($id){
		try{
			$params = [];
			$params['index'] 	= 	"community_categories";
			$params['type']		=	"category";
			$params['id']		=	(int)$id;
			$result				=	$this->es_get($params);
			$robject 			=	new stdClass();
			if (!empty($result['_source'])):
				$row	=	(object)$result['_source'];
				$robject = 	(object)[ 	'category_id' 	=>	$row->category_id,
										'title'			=>	(string)$this->common->entityDecode($row->title),
										'promo_image'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._COMMUNITIES_IMAGES_DIR,
																		'image_path'	=>	(string)$row->promo_image]];
			
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getCommunityDetails($id){
		try{
			$params = 	[];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$id;
			$result				=	$this->es_get($params);
			$robject 			=	new stdClass();
			if (!empty($result['_source'])):
				$row					=	(object)$result['_source'];	
				$robject->community	 	= 	(object)[ 	'community_id' 	=>	$row->community_id ,
														'title'			=>	(string)$this->common->entityDecode($row->title),
														'promo_image'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._COMMUNITIES_IMAGES_DIR,
																						'image_path'	=>	(string)$row->promo_image]];	
		
				$robject->category		=	$this->getCommunityCategoryById($row->category_id);
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	
	public function getCommunities($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['size'] 	= 	1000;
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]],['term' =>	['published' => (int)1]]];
			$params['body']['query']['bool']['filter'] 	= 	[['terms' 	=> 	['category_id'	=> explode(",",$object->ids)]]];
			
			/* for case insensitive sort */
			$params['body']['sort']						= 	[ ['_script' => ['script' 	=> 	"doc['sorting_text'].value.toLowerCase()" ,
																			 'type'		=> 'string' ,
																			 'order'	=>	'ASC']]];
			

			$objects	=	$this->es_search($params);
			$arr	=	[];
			$community_ids	=	[];
			if ($objects['hits']['total']>0):
				/*get all members from community ids */
				foreach ($objects['hits']['hits'] as $cobject):
					if (!empty($cobject['_source'])):
						array_push($community_ids,$cobject['_source']['community_id']);
					endif;
				endforeach;
				
				$es_member				=	new ES_Community_member();
				$community_members		=	$es_member->getCommunityMembers($object,$community_ids);
	
				foreach ($objects['hits']['hits'] as $cobject):
					if (!empty($cobject['_source'])):
						$row		=	(object)$cobject['_source'];
						$arr[$row->category_id][]	=	$this->setOutput($row,$community_members);
					endif;
				endforeach;
				foreach ($object->categories as $cat):
					if (!empty($arr[$cat->category_id])):
						$cat->communities	=	$arr[$cat->category_id];
					endif;
				endforeach;
			endif;
			return $object;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getCommunityById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['id'] 		= 	$object->community_id;
			$result				=	$this->es_get($params);
			$robject			=	new stdClass();
			if (!empty($result['_source'])):
				$row		=	(object)$result['_source'];
				$es_member				=	new ES_Community_member();
				$community_members		=	$es_member->getCommunityMembers($object,array($row->community_id));
				$robject				=	$this->setOutput($row,$community_members);
				$robject->category		=	$this->getCommunityCategoryById($row->category_id);
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($row,$community_members){
		try{
			$robject 	=	new stdClass();
			$robject->community_id		=	(int)$row->community_id;
			$robject->title				=	(string)$this->common->entityDecode($row->title);
			$robject->description		=	(string)$this->common->entityDecode($row->description);
			$robject->promo_image		=	(object)[	'base_url'		=>	(string)_AWS_URL._COMMUNITIES_IMAGES_DIR,
														'image_path'	=>	(string)$row->promo_image];
			$pictures	=	[];
			if (!empty($row->gallery)):
				foreach ($row->gallery as $pic):
					$pObj	=	new stdClass();
					$pObj->picture_id		=	(string)$pic['picture_id'];
					$pObj->picture_path		=	(string)$pic['picture_path'];
					$pObj->picture_caption	=	(string)$this->common->entityDecode($pic['picture_caption']);
					array_push($pictures, $pObj);
				endforeach;
			endif;
			
			$robject->gallary			= 	(object)[	'base_url'		=>	(string)_AWS_URL._COMMUNITIES_IMAGES_DIR,
														'pictures'		=>	$pictures];
			
			$robject->total_members		=	(int)isset($community_members[$robject->community_id]['total_members'])?count($community_members[$robject->community_id]['total_members']):0;
			$robject->approved			=	(boolean)false;
			$robject->rejected			=	(boolean)false;
			$robject->is_member			=	(boolean)false;
			if (isset($community_members[$robject->community_id]['member'])):
				$member					=	$community_members[$robject->community_id]['member'];
				$robject->is_member		=	(boolean)(!empty($member->member_id)?true:false);
				$robject->approved		=	(boolean)(!empty($member->approved)?true:false);
				$robject->rejected		=	(boolean)(!empty($member->rejected)?true:false);
			endif;
			return $robject;
		}	
		catch(Exception $e)
		{	$this->es_error($e);	}
	}	
}
?>