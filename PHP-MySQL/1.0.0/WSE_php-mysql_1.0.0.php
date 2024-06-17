<?php /*Wolf Database Search Engine version 1.0.0 ©James Morris,2024
	Rdistributable under the GNU License v2.0
	Permits creating a custom search engine easily and quickly
*/
class WSE{//One instance per search engine
	//Private attributes
	private $_db;
	private $_queries;
	private $_results;
	private $_weights;
	private $_filters;
	private $_execTime;
	
	//Filters
	public $filter_whitespaces;
	
	//Initialisation
	public function __construct($db){
		$this->_db = $db;
		$this->_queries = array();
		$this->_results = array();
		$this->_weights = array();
		$this->_filters = array();
		$this->_execTime = 0;
		
		$this->filter_whitespaces = function($str,$separator){
			return trim(preg_replace('#\s+#',$separator,$str));
		};
	}
	
	//Custom query add
	public function addCustom($query,$table,$weight=1){
		array_push($this->_queries,array($query,$table,$weight));
	}
	
	//Equals query add
	public function addEquals($row,$table,$weight=1){
		$this->addCustom('SELECT * FROM `:table` WHERE `'.$row.'`=":rowval"',$table,$weight);
	}
	
	//Likes query add
	public function addLikes($row,$table,$weight=1,$notequal=true){
		$this->addCustom('SELECT * FROM `:table` WHERE `'.$row.'` LIKE "%:rowval%"'.($notequal?'AND `'.$row.'`!=":rowval"':''),$table,$weight);
	}
	
	//Likes query add
	public function filter($function){
		array_push($this->_filters,$function);
	}
	
	//Search. Str: the string to search; Separator: the separator for cutting into words
	public function search($str,$separator){
		//formatting query
		foreach($this->_filters as $filter){
			$str = $filter($str,$separator);
		}
		$words = explode($separator,$str);
		
		//Searching
		$this->_results = array();
		$timestart = microtime(true);
		
		foreach($this->_queries as $query){
			foreach($words as $word){
				$reqs = $query[0];
				$reqs = str_replace(':table',@mysql_real_escape_string($query[1]),$reqs);
				$reqs = str_replace(':rowval',@mysql_real_escape_string($word),$reqs);
				
				$req = $this->_db->query($reqs);
				while($res=$req->fetch()){
					$res['_wse_table_'] = $query[1];
					
					if(($index=array_search($res,$this->_results))!==false)
						$this->_weights[$index] += $query[2];
					
					else{
						array_push($this->_results,$res);
						array_push($this->_weights,$query[2]);
					}
				}
			}
		}
		$this->_execTime = microtime(true)-$timestart;
		
		//Ordering
		
		array_multisort($this->_weights,SORT_DESC,$this->_results);
		return $this->_results;
	}
	
	public function getLastExecutionTime(){
		return $this->_execTime;
	}
}
