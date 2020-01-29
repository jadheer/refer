<?php
class ES_Customer_visit extends ESSource
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
	
	public function getVisits($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['published'=>(int)1]]]];
			$params['body']['sort']				=	[['date' => ['order' => 'desc']],['client_visit_id' => ['order' => 'desc']]];
			
			$results	=	$this->es_search($params);
			$arr		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $visit):
					if (!empty($visit['_source'])):
						$row						=	(object)$visit['_source'];
						$robject					=	new stdClass();
						$robject->id				=	(int)$row->client_visit_id;
						$robject->title 			=	(string)$this->common->entityDecode($row->title);
						$robject->description		=	(string)$this->common->entityDecode($row->description);
						$robject->promo_image		=	(string)$row->promo_image;
						$robject->base_url  		=	(string)_AWS_URL._CLIENTVISITS_IMAGES_DIR;
						$robject->pub_datetime 		=	(string)$row->pub_datetime;
						$robject->date		 		=	(string)$row->date;
						$pictures	=	[];
						if (!empty($row->gallery)):
							foreach ($row->gallery as $pic):
								$pObj	=	new stdClass();
								$pObj->picture_path		=	(string)$pic['picture_path'];
								$pObj->picture_caption	=	(string)$this->common->entityDecode($pic['picture_caption']);
								array_push($pictures, $pObj);
							endforeach;
						endif;
						$robject->gallary				=	(object)[	'base_url'		=>	(string)_AWS_URL._CLIENTVISITS_IMAGES_DIR,
																		'pictures'		=>	$pictures];
						array_push($arr , $robject);
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>