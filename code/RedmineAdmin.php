<?php
class RedmineAdmin extends LeftAndMain{
	
	static $url_segment = "support";
	static $url_rule = '/$Action';
	
	static $menu_title = 'Support';
	
	static $url,$username,$password,$apikey;
	
	static function set_redmine_details($url,$user,$password){
		self::$url = $url; //without http
		self::$username = $user;
		self::$password = $password;
		//TODO: limit to one project
	}
	
	function set_api_key($key){
		self::$apikey = $key;
	}
	

	
	/**
	 * Makes a call to API and returns SimpleXML object.
	 */
	protected function apiCall($action,$data = null){
		
		$service = new RestfulService("http://".self::$url."/",0); //REST connection that will expire immediately
		
		$service->httpHeader('Accept: application/xml');
		$service->httpHeader('Content-Type: application/x-www-form-urlencoded');
		
		if(self::$apikey){
			$service->basicAuth(self::$apikey,"blah");
		}else{
			$service->basicAuth(self::$username,self::$password);
		}
		
		$response = $service->request($action,'GET',$data);		
		return $response->simpleXML();
	}
	
	
	/**
	 * Get a dataobject set of project issues
	 * docs http://www.redmine.org/projects/redmine/wiki/Rest_Issues
	 */
	function Issues(){

		$sxml = $this->apiCall('issues.xml');

		$issues = $sxml->xpath('/issues/issue');
		
		$dos = new DataObjectSet();
		
		$cols = array(
			'id',
			'subject',
			'description',
			'done_ratio',
			'estimated_hours'
		);
		
		$datecols = array(
			'created_on',
			'updated_on',
			'start_date',
			'due_date'
		);
		
		$sortablecols = array(
			'project',
			'tracker',
			'status',
			'priority',
			'author',
			'assigned_to'
		);
				
		foreach($issues as $issue){
			$fields = array();
			foreach($cols as $col){
				$fields[$col] = (string)$issue->{$col};
			}
			
			foreach($sortablecols as $col){
				$fields[$col] = (string)$issue->{$col}['name'];
				$fields[$col."Sort"] = (string)$issue->{$col}['id'];
			}
			
			foreach($datecols as $col){
				$date = new SSDatetime('col');
				$date->setValue((string)$issue->{$col});
				$fields[$col] = $date;
			}
			$dos->push(new DataObject($fields));
		}	
		
		return $dos;
		
	}
	
	function NewIssueForm(){
		$fields = new FieldSet(
			new TextField('Subject'),
			new TextareaField('Description')
		);
		
		$actions = new FieldSet(
			new FormAction('doSubmit','Submit New Support Request')
		);
		
		return new Form($this,'NewIssueForm',$fields,$actions);
		
	}
	
	function doSubmit($data,$form){
		
		//TODO: send data to redmine
		//$this->apiCall('issues.xml',$data);
		
	}
}
?>
