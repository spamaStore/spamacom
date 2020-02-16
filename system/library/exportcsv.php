<?php
class ExportCSV {
    
    private $csv_terminated = "\n";
    
    private $csv_separator = ", ";
    
    private $csv_enclosed = '';
    
    private $csv_escaped = "\\";
    
    private $connection;
    
    public $query;
    
    private $output = '';
    
    public $result = array();
    
    public $fields = array();
    
    public function process() {
        
        if(!$this->fields){
            throw new Exception("No data found");
        }
        
        $num_fields = count($this->fields);
        
        $schema = "";
        $fields = array();
        
        
        foreach($this->fields as $field){   
            $f = stripslashes($field);      
            $l = $this->csv_enclosed . str_replace($this->csv_enclosed, $this->csv_escaped . $this->csv_enclosed, $f) . $this->csv_enclosed;
            $schema .= $l;
            $schema .= $this->csv_separator;
        }
        
        $out = trim(substr($schema, 0, -1));
        $out .= $this->csv_terminated;

        foreach($this->result as $result){  
            $record = '';               
            foreach ($this->fields as $key=>$field) {
                if($result[$field] != 0 || $result[$field] != ''){
                    $record .= $this->csv_enclosed . str_replace($this->csv_enclosed, $this->csv_escaped . $this->csv_enclosed, $result[$field]) . $this->csv_enclosed;                                     
                } 
                else {
                    $record .= '';
                }
                $record .= ($key+1) < $num_fields ? $this->csv_separator : '' ;
            }
            $out .= $record;
            $out .= $this->csv_terminated;          
        }   
        
        $this->output = $out;
    }
    
    public function write($filepath) {      
        if(!file_exists($filepath)){
            throw new Exception("file to write to could not be found");
        }       
        $file = fopen($filepath, "a");  
        fputs($file, $this->output);    
        fclose($file);      
    }
    
    public function download($filename){
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Length: " . strlen($this->output));
        // Output to browser with appropriate mime type, you choose ;)
        header("Content-type: text/x-csv");
        //header("Content-type: text/csv");
        //header("Content-type: application/csv");
        header("Content-Disposition: attachment; filename=$filename");
        echo $this->output;
        exit;
    }
    
}