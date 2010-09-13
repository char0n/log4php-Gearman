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
 * @category tests
 * @license New BSD License
 * @author char0n (Vladimir Gorej) <gorej@mortality.sk>	 
 * @package log4php
 * @subpackage appenders
 * @version 1.0-b1
*/
class LoggerAppenderGearmanTest extends PHPUnit_Framework_TestCase {
		
	public static $gmw;
	protected static $appender;
	protected static $event;
	
	public static function setUpBeforeClass() {
		self::$gmw              = new GearmanWorker();
		self::$gmw->addServer();
		self::$gmw->addFunction('log4php_logging_event', 'log4php_logging_event');
		self::$appender         = new LoggerAppenderGearman('gearman_appender');
		self::$event            = new LoggerLoggingEvent("LoggerAppenderGearmanTest", new Logger("TEST"), LoggerLevel::getLevelError(), "testmessage");
	}
	
	public static function tearDownAfterClass() {
		self::$gmw = null;
		self::$appender->close();
		self::$appender = null;
		self::$event = null;
	}
	
	public function test__construct() {
		$appender = new LoggerAppenderGearman('gearman_appender');
		$this->assertTrue($appender instanceof LoggerAppenderGearman);
	}	
	
	public function testSetGetHosts() {
		$expected = 'localhost:4730';
		self::$appender->setHosts($expected);		
		$result = self::$appender->getHosts();
		$this->assertEquals($expected, $result, 'Hosts doesn\'t match expted value');
	}	
	
	public function testActivateOptions() {
		try {
			self::$appender->activateOptions();	
		} catch (Exception $ex) {
			$this->fail('Activating appender options was not successful');
		}		
	}	
	
	public function testAppend() {
		self::$appender->append(self::$event);
		ob_start();
		self::$gmw->work();
		$workload = ob_get_clean();
		$this->assertContains('testmessage', $workload, 'Workload shoud contain logging event text');
	}
		
	
	public function testAppendException() {
		$throwable = new GearmanTestingException('test');
		self::$appender->append(new LoggerLoggingEvent("LoggerAppenderMongoDBTest", new Logger("TEST"), LoggerLevel::getLevelError(), "testmessage", microtime(true), $throwable));				 		
		ob_start();
		self::$gmw->work();	
		$workload = ob_get_clean();
		$this->assertContains('exception', $workload, 'Workload should contain throwable info');
	}

	public function testAppendInnerException() {
		$throwable = new GearmanTestingException('test', 0, new GearmanTestingException('test inner'));
		self::$appender->append(new LoggerLoggingEvent("LoggerAppenderMongoDBTest", new Logger("TEST"), LoggerLevel::getLevelError(), "testmessage", microtime(true), $throwable));				 		
		ob_start();
		self::$gmw->work();	
		$workload = ob_get_clean();
		$this->assertContains('innerException', $workload, 'Workload should contain throwable info about inner exception');		
	}
	
	public function testClose() {
		self::$appender->close();
	}	
}

function log4php_logging_event($job) {
	var_dump(json_decode($job->workload(), true));
	return true;
}

if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
	class GearmanTestingException extends Exception {}
} else {
	class GearmanTestingException extends Exception {
				
		protected $cause;
				
		public function __construct($message = '', $code = 0, Exception $ex = null) {
			
			parent::__construct($message, $code);
				$this->cause = $ex;
			}
				
			public function getPrevious() {
				return $this->cause;
			}
	}
}
?>