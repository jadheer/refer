<?php
class ES_Like extends ESSource {
	var $component;
	public function __construct(){
		parent::__construct();
		$this->component = ['NW' => (object)['index' => 'news', 'docType' => 'news'],
							'MT' => (object)['index' => 'microtips', 'docType' => 'microtip'],
							'CM' => (object)['index' => 'community_posts', 'docType' => 'community_post'],
							'A' => (object)['index' => 'articles', 'docType' => 'article'],
							'W' => (object)['index' => 'wall_posts', 'docType' => 'wall_post'],
							'CW' => (object)['index' => 'customer_wins', 'docType' => 'customer_win'],
							'FE' => (object)['index' => 'facilities', 'docType' => 'facility'],
							'LE' => (object)['index' => 'learnings', 'docType' => 'learning'],
							'EV' => (object)['index' => 'events', 'docType' => 'event'],
							'CQ' => (object)['index' => 'chatquestions', 'docType' => 'chatquestion'],
							'SE' => (object)['index' => 'som_employees', 'docType' => 'som_employee'],
							'BU' => (object)['index' => 'breakout_updates', 'docType' => 'breakout_update'],
							'MVU' => (object)['index' => 'microgive_updates', 'docType' => 'microgive_update'],
							'MP' => (object)['index' => 'mlthirty_posts', 'primary' => 'mlthirty_challenge_post_id', 'docType' => 'mlthirty_post'],
							'CA' => (object)['index' => 'cheer_employee_awards', 'primary' => 'cheer_employee_award_id', 'docType' => 'cheer_employee_award'],
							'CTN' => (object)['index' => 'cheer_top_nominators', 'primary' => 'nominator_id', 'docType' => 'cheer_top_nominator'],
							'CTR' => (object)['index' => 'cheer_top_recipients', 'primary' => 'recipient_id', 'docType' => 'cheer_top_recipient']
							];
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function addLike($object){
		try{
			$params = [];
			$params['index']  	= 	$this->component[$object->type]->index;
			$params['type']  	= 	$this->component[$object->type]->docType;
			$params['id']    	= 	(int)$object->refer_id;

			$params['body']['script']['inline']	=	'ctx._source.likes.add(params.likes)';
			$params['body']['script']['params']	=	['likes' => (int)$object->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function deleteLike($object){
		try{
			$params = [];
			$params['index']  	= 	$this->component[$object->type]->index;
			$params['type']  	= 	$this->component[$object->type]->docType;
			$params['id']    	= 	(int)$object->refer_id;

			$params['body']['script']['inline']	=	"ctx._source.likes.removeAll(Collections.singleton(params.likes))";
			$params['body']['script']['params']	=	['likes' => (int)$object->employee_id];

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function refresh($Index){
		try{
			$params = [];
			$params['index']  	= 	$this->component[$Index]->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>
