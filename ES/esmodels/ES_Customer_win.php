<?php
class ES_Customer_win extends ESSource
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
	
	
	public function getWins($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['published'=>(int)1]]]];
		
			$params['body']['sort']	=	[['date' => ['order' => 'desc']], ['win_id' => ['order' => 'desc']]];
			
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
		
	private function setOutput($results,$object){
		try{
			$arr 		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $win_obj):
					if (!empty($win_obj['_source'])):
						$row						=	(object)$win_obj['_source'];
						$robject					=	new stdClass();
						$robject->id				=	(int)$row->win_id;
						$robject->title 			=	(string)$this->common->entityDecode($row->title);
						$robject->description		=	(string)$this->common->entityDecode($row->description);
						$robject->promo_image		=	(string)$row->promo_image;
						$robject->base_url			=	(string)_AWS_URL._CLIENTVISITS_IMAGES_DIR;
						$robject->pub_datetime 		=	(string)$row->pub_datetime;
						$robject->date				=	(string)$row->date;
						$robject->comment_count 	=	(int)count($row->comments);
						$robject->like_count 		=	(int)count($row->likes);
						$robject->liked 			=	(boolean)(in_array($object->employee_id,$row->likes));
					endif;
					array_push($arr, $robject);
				endforeach;
			endif;
			return $arr;	
		}catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>