<?php
/**
 * The New BSD License
 *
 * Copyright (c) 2010, Vladimir Gorej
 * All rights reserved.
 *	
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *	
 *		* Redistributions of source code must retain the above copyright notice,
 *			 this list of conditions and the following disclaimer.
 *		 
 *		 * Redistributions in binary form must reproduce the above copyright notice,
 *			 this list of conditions and the following disclaimer in the documentation
 *			 and/or other materials provided with the distribution.
 *			 
 *		 * The name of author may not be used to endorse or promote products derived from
 *			 this software without specific prior written permission.
 *	
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @license New BSD License 
 * @author char0n (Vladimir Gorej) <gorej@mortality.sk>	 
 * @package log4php
 * @subpackage appenders
 * @version 1.0-b1
*/


// Format of log event (for exception):
// {
//    "timestamp": "1284366746.7",
//    "level":"ERROR",
//    "thread":"2556",
//    "message":"testmessage",
//    "fileName":"NA",
//    "method":"getLocationInformation",
//    "lineNumber":"NA",
//    "className":"LoggerLoggingEvent",
//    "exception":{
//        "message":"exception2",
//        "code":0,
//        "stackTrace":"stackTrace of Exception",
//        "innerException":{
//            "message":"exception1",
//            "code":0,
//            "stackTrace":"stactTrace of inner Exception"
//        }
//    }
// } 
class LoggerAppenderGearman extends LoggerAppender {
	
	protected static $DEFAULT_GEARMAN_HOST      = 'localhost';
	protected static $DEFAULT_GEARMAN_PORT      = 4730;
	protected static $DEFAULT_GEARMAN_TASK_NAME = 'log4php_logging_event';
	
	protected $hosts;
	protected $gearmanTaskName;
	protected $connection;
	
	public function __construct($name = '') {
		parent::__construct($name);
		$this->hosts           = self::$DEFAULT_GEARMAN_HOST.':'.self::$DEFAULT_GEARMAN_PORT;
		$this->gearmanTaskName = self::$DEFAULT_GEARMAN_TASK_NAME;
		
		$this->requiresLayout = false;
	}
	
	public function setHosts($hosts) {
		$this->hosts = $hosts;
	}
	
	public function getHosts() {
		return $this->hosts;
	}
	
	public function setGearmanTaskName($name) {
		$this->gearmanTaskName = $name;
	}
	
	public function getGearmanTaskName() {
		return $this->gearmanTaskName;
	}
	
	/**
	 * Setup gearman connection.
	 * Based on defined options, this method connects to hosts defined in {@link $hosts}
	 * Theoretically exception will be raised only when gearman exception is not available.
	 * There is no I/O during addServer() call
	 * and creates a {@link $connection} 
	 * @return boolean true if all ok.
	 * @throws an Exception if the attempt to connect to the requested gearman hosts fails.
	 */	
	public function activateOptions() {
		try {
			$this->connection = new GearmanClient();
			$this->connection->addServers($this->hosts);				
		} catch (Exception $ex) {
			$this->canAppend = false;
			throw new LoggerException($ex);
		}
		
		$this->canAppend = true;
		return true;
	}
	
	/**
	 * Appends a new event to the gearman job servers.
	 * 
	 * @throws LoggerException	If the pattern conversion or the job submissions statement fails.
	 */
	public function append(LoggerLoggingEvent $event) {		 
		$document     = $this->loggingEventToArray($event);
		$jsonDocument = json_encode($document);
		$this->connection->doBackground($this->gearmanTaskName, $jsonDocument);
		if ($this->connection->returnCode() != GEARMAN_SUCCESS) {
			throw new LoggerException('German task submittion failed', 0);
		}
	}	
	
	protected function loggingEventToArray(LoggerLoggingEvent $event) {
		$document = array(
			'timestamp' => $event->getTimestamp(),
			'level'     => $event->getLevel()->toString(),
			'thread'    => $event->getThreadName(),
			'message'   => $event->getMessage()
		);
		
		if ($event->getLocationInformation() !== null) {
			$document['fileName']   = $event->getLocationInformation()->getFileName();
			$document['method']     = $event->getLocationInformation()->getMethodName();
			$document['lineNumber'] = $event->getLocationInformation()->getLineNumber();
			$document['className']  = $event->getLocationInformation()->getClassName();
		}
        
		if ($event->getThrowableInformation() !== null) {
			$document['exception'] = $this->exceptionToArray($event->getThrowableInformation()->getThrowable());										
		}
        
		return $document;
	}	
	
	protected function exceptionToArray(Exception $ex) {
		$document = array(				
			'message'    => $ex->getMessage(),
			'code'       => $ex->getCode(),
			'stackTrace' => $ex->getTraceAsString(),
		);
                        
		if (method_exists($ex, 'getPrevious') && $ex->getPrevious() !== null) {
			$document['innerException'] = $this->exceptionToArray($ex->getPrevious());
		}
			
		return $document;
	}	
	
	/**
	 * Closes the connection to the gearman servers
	 */	
	public function close() {
		if($this->closed != true) {
			$this->connection = null;
			$this->closed = true;
		}
	}		 
		
	public function __destruct() {
		$this->close();
	}	
}
?>