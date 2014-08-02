#Importing of the needed libraries
BRIDGECLIENT_DIR = '/usr/lib/python2.7/bridge/'

import sys
import os
sys.path.insert(0, BRIDGECLIENT_DIR)
from tcp import TCPJSONClient
from time import sleep


#Definition of a couple key/value for testing purpose
BRIDGE_TEST_KEY = 'bridgeTestKey'
BRIDGE_TEST_VALUE = 'bridgeTestValue'

# The bridge class used to communicate 
# with the ATmega via mailbox
#
# Despite of the standard Python bridge,
# here the connection is not opened and closed
#every time we are going to get/put a value
# into the mailbox. The assumption is that the
# only process able to use this mailbox is the 
# engine we are developing so it is the only 
# process that will interact with the ATmega and 
# that will use this channel. The advantage is that 
# we are going to save time.
class WnWBridge:

	# The constructor
	def __init__(self):
		self.bridge = None
		self.errorMessage = ''
	
	# The initialization of the channel (test included)
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

	# Close the channel
	def close(self):
		self.errorMessage = ''
		if self.bridge:
			self.bridge.close()

	# Return the error message
	def getErrorMessage(self):
		return self.errorMessage			

	# Return the value associated to the passed key
	# _key the key we want to know the value associated: string
	# The function is synchronous and waits up to 10 seconds
	# before returning None (if the value is not retrieved)
	# Returns a string
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
					return None                                                  
			timeout -= 0.1                                                            
			sleep(0.1)
		return None

	# Set the couple key/value into the mailbox
	# _key the key: string
	# _value the vale: string
	# The function read the value just written and
	# returns False if the value is not the same
	# or the timeout has been exceeded
	# Otherwise it returns True
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
						return True                              
				except Exception as error:
					self.errorMessage = 'Bridge execution exception: ' + str(error)
					return False                                                  
			timeout -= 0.1                                                            
			sleep(0.1)
		return False  
