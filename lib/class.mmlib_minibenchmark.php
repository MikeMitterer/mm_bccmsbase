<?php

class mmlib_minibenchmark {
	var $timerStart;
	var $timerLast;
	var $showDetails = false;
	var $summarizeTable = array();
	
  function init($showDetails = false) {
  	$this->showDetails = $showDetails;
  	$this->summarizeTable =  array();
  }
  
  function showstat() { $this->summarize(); }
  
  function summarize() {
  	array_multisort($this->summarizeTable,SORT_NUMERIC,SORT_DESC);
  	t3lib_div::debug($this->summarizeTable,'benchmessage-summarize=');	
  }
  
  function start($event) {
    $message = sprintf("timer start: %s", $event);
    $this->show($message);
    
    list($low, $high) = explode(" ", microtime());
    $this->timerStart = $high + $low;

    $this->summarizeTable[$event] = 0;
    $this->timerLast = $this->timerStart;
    return $this->timerStart;
  }

  function next($event) {
    list($low, $high) = explode(" ", microtime());
    $timerCurrent    = $high + $low;
    $used = $timerCurrent - $this->timerLast;
    $message = sprintf("timer next: %s (%8.4f)", $event, $used);
    
    $this->summarizeTable[$event] += $used;
    $this->show($message);
	
    $this->timerLast = $timerCurrent;
    return $this->timerLast;
  }
  
  function stop($event) { $this->next($event); }
  
  function show($message) {
  	if($this->showDetails) { 
  	t3lib_div::debug($message,'benchmessage=');
  	flush();
  	}
  }
}


?>