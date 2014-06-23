#!/usr/bin/python

# Watch 'n' Water engine
#
# This is the core of the WnW project, where the logic of the watering system is implemented
# and the commands are actually sent to the sketch uploaded into the Arduino
#
# LICENSE: GPL v3

VERBOSE = 1
LOG_FILENAME = '/mnt/sda1/wnw/log/engine.log'
DB_FILENAME = '/mnt/sda1/wnw/wnwdb.sqlite'
BRIDGECLIENT_DIR = '/usr/lib/python2.7/bridge/'
SUCCESS = 0
GENERIC_ERROR = 1
SOIL_MOISTURE_SENSOR = 0
SOIL_MOISTURE_THRESHOLD = 1000
BRIDGE_TEST_KEY = 'bridgeTestKey'
BRIDGE_TEST_VALUE = 'bridgeTestValue'

import sqlite3 as lite
import sys
import time
import logging
sys.path.insert(0, BRIDGECLIENT_DIR) 
from time import sleep

_DB_CON_ = None
_BRIDGE_ = None
_STAY_IN_THE_LOOP_ = False
_OUTPUTS_ = None
_WATERING_PLAN_ = None

########################
# Functions definition #
########################

def initDB():
	global _DB_CON_
	if not _DB_CON_:
		logging.info('Connecting to the DB (%s)...' % DB_FILENAME)
		_DB_CON_ = lite.connect(DB_FILENAME)
		if not _DB_CON_:
			logging.error('Database not accessible. Exiting...')
			sys.exit(1)
		else:
			logging.info('Database connection established.')
	else:
		logging.warning('Database connection already established')
		sys.exit(1)


def initBridge():
	global _BRIDGE_
	if not _BRIDGE_:
		logging.info('Connecting to the Bridge...')
		from tcp import TCPJSONClient
		_BRIDGE_ = TCPJSONClient('127.0.0.1', 5700)
	else:
		logging.warning('Bridge connection already established')
		sys.exit(1)
	logging.info('Bridge connection established.')
	if sendValue(BRIDGE_TEST_KEY,BRIDGE_TEST_VALUE) == BRIDGE_TEST_VALUE:
		logging.info('Bridge teste success.')
	else:
		logging.error('Bridge test failed. Exiting...')
		sys.exit(1)

def getValue(_key):
	global _BRIDGE_
	_BRIDGE_.send({'command':'get', 'key':_key})
	timeout = 10;                                          
	while timeout>=0:                             
		r = _BRIDGE_.recv()                      
		if not r is None:                                                
			try:                                 
				if r['key'] == _key:                               
					return r['value']                               
			except:                                             
				pass                                                  
		timeout -= 0.1                                                            
		sleep(0.1)
	return None                         

def sendValue(_key, _value):
	global _BRIDGE_
	_BRIDGE_.send({'command':'put', 'key':_key, 'value':_value})
	timeout = 10;                                          
	while timeout>=0:                             
		r = _BRIDGE_.recv()                      
		if not r is None:                                                
			try:                                 
				if (r['key'] == _key and r['value'] == _value):                               
					return r['value']                               
			except:                                             
				pass                                                  
		timeout -= 0.1                                                            
		sleep(0.1)
	return None                         

##################
# Startup procedure
##################

def startup():
	global _DB_CON_;
	global _BRIDGE_;
	global _OUTPUTS_;
	global _WATERING_PLAN_;


	# Prevent the main loop to be executed in case startup procedure fails
	global _STAY_IN_THE_LOOP_ = false;

	# Ask RTC to align the system date
	logging.debug('System datetime is ' + time.strftime('%m/%d/%Y %H:%M:%S'));
	logging.debug('Aligning the system with the RTC datetime...');
	putValue('align_datetime','1');
	logging.debug("DELAY 2000...");
	delay(2000);
	if getValue('align_datetime') != '0':
		logging.error('ERROR: Onboard RTC doesn\'t respond');
		return None
	else:
		_datetime = getValue('datetime')
			
	logging.debug("System datetime post alignment is " + time.strftime('%m/%d/%Y %H:%M:%S'));

	# Retrieve the outputs
	logging.info('Retrieving actuators...')
	retrieve_outputs()
	if len(_OUTPUTS_) == 0:
		logging.warning('No actuator defined')
		return None

	# Retrieve the watering plan (only valid entries)
	logging.info('Retrieving watering plan...')
	retrieve_watering_plan()
	if len(_EWATERING_PLAN_ == 0):
		logging.warning('WARNING: No watering plan defined')
		return None
		
	# Retrieve the watering plan (only valid entries)
	logging.info('Get actuators impacted by the watering plan')
	outputArray = calculate_impacted_actuators()
	if len(outputArray) == 0:
		logging.warning('No actuator impacted by the defined watering plan')
		return None
	else:
		logging.info('The watering plan impacts %i actuator(s)' % len(outputArray))

	# Startup procedure completed successfully
	_STAY_IN_TH_LOOP_ = True
	return outputArray




##################
# MAIN 
##################

try:
	logging.basicConfig(filename=LOG_FILENAME,level=logging.DEBUG,format='%(asctime)s %(levelname)s %(message)s', datefmt='%m/%d/%Y %H:%M:%S')
	logging.info('\n\n')
	logging.info('    <-- WnW Engine started -->')
	
	#
	# Connect to the database
	# Connect to the bridge
	#
	initDB()
	initBridge()
	
	#
	# Startup process
	#
	logging.info('Calling the startup procedure...')
    global _STAY_IN_THE_LOOP_ = True
    _outputArray = startup()

	#
	# Main loop
	#
    if _STAY_IN_THE_LOOP_:
		logging.info('Running the main loop...')
    while _STAY_IN_THE_LOOP_:


	cur = _DB_CON_.cursor()
	cur.execute('SELECT * from actuators')

	rows = cur.fetchall()

	for row in rows:
		print row

except lite.Error, e:

	logging.error("Error %s:" % e.args[0])
	sys.exit(1)

finally:

	if _DB_CON_:
		logging.info('Closing DB connection...')
		_DB_CON_.close()
	if _BRIDGE_:
		logging.info('Closing BRIDGE connection...')
		_BRIDGE_.close()
		

