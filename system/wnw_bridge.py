BRIDGECLIENT_DIR = '/usr/lib/python2.7/bridge/'

import sys
import os
sys.path.insert(0, BRIDGECLIENT_DIR)
from tcp import TCPJSONClient
from time import sleep

BRIDGE_TEST_KEY = 'bridgeTestKey'
BRIDGE_TEST_VALUE = 'bridgeTestValue'


class WnWBridge:

	def __init__(self):
		self.bridge = None
		self.errorMessage = ''
		
	def init(self):
		self.errorMessage = ''
		if not self.bridge:		
			self.bridge = TCPJSONClient('127.0.0.1', 5700)
		else:
			self.errorMessage = 'Bridge connection already established'
			return False
		if self.putValue(BRIDGE_TEST_KEY,BRIDGE_TEST_VALUE) == BRIDGE_TEST_VALUE:
			return True
		else:
			self.errorMessage = 'Bridge connection established but test failed'
			return False

	def close(self):
		self.errorMessage = ''
		if self.bridge:
			self.bridge.close()

	def getErrorMessage(self):
		return self.returnMessage			

	def getValue(self, _key):
		if self.bridge == None:
			self.errorMessage = 'Bridge is not connected, establish connection before getting any value'
			return False
		self.bridge.send({'command':'get', 'key':_key})
		timeout = 10;                                          
		while timeout>=0:                             
			r = self.bridge.recv()                      
			if not r is None:                                                
				try:                                 
					if r['key'] == _key:                               
						return str(r['value'])                       
				except Exception as error:
					self.errorMessage = 'Bridge execution exception: ' + str(error)
					return False                                                  
			timeout -= 0.1                                                            
			sleep(0.1)
		return True                         

	def putValue(self, _key, _value):
		if self.bridge == None:
			self.errorMessage = 'Bridge is not connected, establish connection before setting any value'
			return False
		self.bridge.send({'command':'put', 'key':_key, 'value':_value})
		timeout = 10;                                          
		while timeout>=0:                             
			r = self.bridge.recv()                      
			if not r is None:                                                
				try:                                 
					if (r['key'] == _key and r['value'] == _value):                               
						return r['value']                               
				except Exception as error:
					self.errorMessage = 'Bridge execution exception: ' + str(error)
					return False                                                  
			timeout -= 0.1                                                            
			sleep(0.1)
		return True  