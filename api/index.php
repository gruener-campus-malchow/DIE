<?php

require_once('./models/model.php');

class Api {
	private $db;
	private $json;
	private $config;
	private $debug;
	private $debugMessages;
	private $request;
	
    public function __construct(){
		
		require_once('./config/config.php');
		$this->config = $config;
				
		$this->debug = $this->config['debug'];
		
		if($this->debug){
			$this->debugMessages = array();
		}
        header('Content-Type: application/json');
        
        $this->dbConnect();
        
        $this->testUrlHandling();
        //$this->createTest();
        //$this->testDB();
        
        $this->request = array();
        $request_uri = $_SERVER['REQUEST_URI'];
        //echo $request_uri.' ';
        $length = strlen($this->config['BASE_URL']);
        //echo $length.' ';
        //$request_uri = ltrim($request_uri, $this->config['BASE_URL']);
		$request_uri = substr($request_uri, $length-1);
		//echo $request_uri.' ';
        foreach(explode('/', $request_uri) as $element)
        {
			if($element != '')
			{
				array_push($this->request, $element);
			}
		}
        
        //print_r($this->request);
        
        $modelList = $this->readAvaliableModels();
        
        if(!in_array($this->request[0].'.php',$modelList))
        {
			$data = array('You asked for an api-object, I do not know. This should be a 404!');
			//header("HTTP/1.1 404 Not Found");
			//exit();
		}
		else
		{
			$modelObject = $this->instantiateAvaliableModel($this->request[0].'.php');
		
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				// POST WORKFLOW
				if ($this->request[0]!='' and !array_key_exists(1,$this->request))
				{
					$data = $modelObject->update($this->request[0]);
					//$data = array('try the GET path and boring Things');
				}
				elseif ($this->request[0]!='' and $this->request[1]!='')
				{
					$data = $modelObject->postSpecial();    
					//$data = array('try the GET path and Special Things');
				}
				else
				{
					$data = $modelObject->create();
					//$data = array('try the GET path');
				}
			}
			else
			{
				//GET WORKFLOW
				if ($this->request[0]!='' and !array_key_exists(1,$this->request))
				{
					$data = $modelObject->readSingle($this->request[1]);
					//$data = array('try the GET path and boring Things');
				}
				elseif ($this->request[0]!='' and $this->request[1]!='')
				{
					$data = $modelObject->getSpecial();    
					//$data = array('try the GET path and Special Things');
				}
				else
				{
					$data = $modelObject->readAll();
					//$data = array('try the GET path');
				}
			}
		}
		if ($this->debug){
			$data = array($this->request, $data);
		}
        
        $this->json = json_encode($data);
        
        if($this->debug){
			$this->json = json_encode($this->debugMessages);
		}
	}
    private function dbConnect(){
		
		$this->db = new PDO('mysql:host='.$this->config['DB_HOST'].
								';dbname='.$this->config['DB_NAME'],
								$this->config['DB_USER'], 
								$this->config['DB_PASSWORD']);
        if ($this->debug){
			$this->debugMessages = array_merge(array(
				'errorcode' => $this->db->errorCode(),
				'errorinfo' => $this->db->errorInfo()
			), $this->debugMessages);
		}
       // $this->json = json_encode($data);
    }
    private function executeSELECT($query){
		$statement = $this->db->prepare($query);
        $statement->execute();
        $data = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        if ($this->debug){
			$this->debugMessages = array_merge(array(
				'errorcode' => $this->db->errorCode(),
				'errorinfo' => $this->db->errorInfo()
			), $this->debugMessages);
		}
        
        return $data;
	}
	public function getJson()
	{
		return $this->json;
	}
	private function createTest(){
		$collection = array();
		
		$testmodel = new model('item',$this->db, $this->debug);
		$data = $testmodel->readAll();
		array_push($collection, $data);
		if ($this->debug){
			array_push($this->debugMessages, $testmodel->debugMessages);
		}
				
		$testmodel = new model('pictures',$this->db, $this->debug);
		$data = $testmodel->readAll();
		array_push($collection, $data);
		if ($this->debug){
			array_push($this->debugMessages, $testmodel->debugMessages);
		}

		$testmodel = new model('item',$this->db, $this->debug);
		$data = $testmodel->readSingle('1');
		array_push($collection, $data);
		if ($this->debug){
			array_push($this->debugMessages, $testmodel->debugMessages);
		}

		$this->json = json_encode($collection);
	}
	private function testDB(){
		
		$data = $this->executeSELECT('SELECT * FROM item');
		$this->json = json_encode($data);
	}
	
	private function testUrlHandling(){
		$data = array();
		array_push($data, $_SERVER['REQUEST_URI']);
		$this->json = json_encode($data);
	}
	
	private function readAvaliableModels()
	{
	    // öffnen des Verzeichnisses
		if ( $handle = opendir('./models/') )
		{
			// einlesen der Verzeichnisses
			$data = array();
			//array_push($data, 'put some files into list');
			while (($file = readdir($handle)) !== false)
			{
				if (array_pop(explode('.',$file))== 'php')
				{
					array_push($data, $file);
				}
				
			}
			closedir($handle);
		}else{
			array_push($this->debugMessages, 'not a working directory');
		}
		return $data;
	}
	private function instantiateAvaliableModel($modelFile)
	{
		require_once('./models/'.$modelFile);
		$modelname = explode('.',$modelFile)[0];
		return new $modelname($modelname,$this->db,$this->debug, $this->request, $this->config);
	}
	
	
}

//echo "Hello";

$api = new Api();
echo $api->getJson();


?>
