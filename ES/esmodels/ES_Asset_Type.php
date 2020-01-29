<?php 
class ES_Asset_Type  extends ESSource 
{
	var $index;
	var $type;
	public function __construct()
	{	
		parent::__construct();
		$this->index	=	"asset_types";
		$this->type		=	"asset_type";
	}

    public function __destruct()
	{	parent::__destruct();	}

	
	public function getAssets(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['body']['query']['bool']['must'] 	= 	[	['term' 	=>  ['enabled'				=>		(int)1]],
																['nested'	=>	[   'path' 		   		=> 		'assets',
																					'query'		    	=>  	['bool' => ['must' => ['term' => ['assets.enabled' => (int)1]]]],
																					'inner_hits'		=>		['from'	=>	(int)0, 'size'	=>	(int)100 , 'sort' =>['assets.asset_type_id' => ['order'	=>	'asc' ]] ]]]];
														

			$params['body']['sort']				=	[['asset_category_id' => ['order'	=>	'asc']]];
			$results	=	$this->es_search($params);
			$rObjects	=	[];
			if ($results['hits']['total']>0):
				$es_icon				=		new ES_Icon();
				$objIcons				=		$es_icon->getIcons();
				if(!empty($objIcons)):
					$objIcons	=	array_column($objIcons, NULL ,"icon_id");
				endif;
				foreach ($results['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row							=		(object)$object['_source'];
						$inner_doc						=		$object['inner_hits']['assets']??[];
						$robject 						=		new stdClass();
						$robject->category_id			=		(int)$row->asset_category_id;
						$robject->ml_title				=		(string)$this->common->entityDecode($row->ml_business_function);	
						$robject->title					=		(string)$this->common->entityDecode($row->display_text);
						$assets							=		[];
						if(!empty($inner_doc)):
							foreach ($inner_doc['hits']['hits']  as  $asset):
								$lobj						=		new stdClass();
								$asset						=		(object)$asset['_source'];
								$lobj->list_id				=		(int)$asset->ml_entitlement_list_id;
								$lobj->title				=		(string)$this->common->entityDecode($asset->display_text);
								$lobj->ml_title				=		(string)$this->common->entityDecode($asset->ml_entitlement_name);
								$lobj->icon_name			=		(string)isset($objIcons[$asset->icon_map_id])?(string)$this->common->entityDecode($objIcons[$asset->icon_map_id]->title) : "";
								array_push($assets,$lobj);
							endforeach;
						endif;
						$robject->assets				=		$assets;
						array_push($rObjects, $robject);
					endif;
				endforeach;
			endif;
			return $rObjects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
}
?>