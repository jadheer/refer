<?php
class ES_Joinee_checklist extends ESSource
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
	
	
	public function getChecklists($object){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['geography_id'=>(int)$object->geography->geography_id]],
																]];
			
			$params['body']['sort']	=		['checklist_id'	=>	['order'	=>	'asc']];
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		$arr 		=	[];
		if ($results['hits']['total']>0):
			
			$es_itemlist		=	new ES_Joinee_itemlist();
			$empChecklist		=	$es_itemlist->getEmployeeItems($object);
			foreach ($results['hits']['hits'] as $checklist_obj):
				if (!empty($checklist_obj['_source'])):
					$row					=	(object)$checklist_obj['_source'];	
					$robject				=	new stdClass();
					$robject->checklist_id	=	(int)$row->checklist_id;
					$robject->title			=	(string)$this->common->entityDecode($row->title);
					$items					=	[];
					if (!empty($row->items)):
						foreach ($row->items as $item):
							$pObj	=	new stdClass();
							$pObj->item_id		=	(int)$item['item_id'];
							$pObj->item_text	=	(string)$this->common->entityDecode($item['item_text']);
							$pObj->url 			=	(string)$item['url'];
							$pObj->checked		=	(in_array($item['item_id'],$empChecklist))?true:false;
							array_push($items, $pObj);
						endforeach;
					endif;
					$robject->items				=	$items;
					array_push($arr, $robject);
				endif;
			endforeach;
		endif;
		return $arr;
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