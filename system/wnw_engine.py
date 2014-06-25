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
import os
import time
import logging
sys.path.insert(0, BRIDGECLIENT_DIR) 
from time import sleep
import subprocess

_DB_CON_ = None
_BRIDGE_ = None
_STAY_IN_THE_LOOP_ = False
_OUTPUTS_NUMBER_ = 0
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
	if putValue(BRIDGE_TEST_KEY,BRIDGE_TEST_VALUE) == BRIDGE_TEST_VALUE:
		logging.info('Bridge test: success.')
	else:
		logging.error('Bridge test: failed. Exiting...')
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
					return str(r['value'])                       
			except:                                             
				pass                                                  
		timeout -= 0.1                                                            
		sleep(0.1)
	return None                         

def putValue(_key, _value):
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
#   Following the definition of the startup() procedure
#   and all the functions used inside it
##################

#
# Get the current status of the output:
# 0 = inactive
# 1 = active
#
def getCurrentStatus(_output):
	_retVal = getValue('outputResponse')
	if _retVal == None: 
		return None
	else:
		if len(_retVal) < _output+1:
			logging.error("You are asking the status of an output that is not accessible (output=%d,outputResponse='%s'" %(_output,_retVal))
		else:
			if _retVal[_output] == '0':
				return 0
			else:
				return 1
		return None

# Retrieve the outputs
def retrieve_outputs():
	global _OUTPUTS_NUMBERS_
	_OUTPUTS_NUMBERS_ = 0
	
	try:
		cur = _DB_CON_.cursor()
		cur.execute('SELECT count(id) FROM outputs')
		rows = cur.fetchall()
		for row in rows:
			_OUTPUTS_NUMBERS_ = row[0]
	except Exception as error:
		logging.error("SQLite3 execution exception: %s" % error)
		
	logging.debug('Retrieved %i output(s)' % _OUTPUTS_NUMBERS_)

# Retrieve watering plan
def retrieve_watering_plan():
	global _WATERING_PLAN_
	_WATERING_PLAN_ = []

	cur = _DB_CON_.cursor()
	query = "SELECT id, output, strftime('%H:%M', [from]) as start_time, duration, weekdays_bitmask, is_forced FROM watering_plan"
	query += " WHERE is_valid = 1"	
	try:
		cur.execute(query)
		rows = cur.fetchall()

		for row in rows:
			logging.debug("PlanID = %i -> outputID = %i -> @ %s, duration %i min(s), weekdays '%s' (forced = %i)" % (row[0], row[1], row[2], row[3], row[4], row[5]))
			item = {"output":row[1], "startTime":row[2], "duration":row[3], "weekdays":row[4], "isForced":row[5]}
			_WATERING_PLAN_.append(item)
	except Exception as error:
		logging.error("SQLite3 execution exception: %s" % error)
		
	logging.debug('Loaded %i record for the watering plan' % len(_WATERING_PLAN_))

# Startup procedure
def startup():
	global _DB_CON_
	global _BRIDGE_
	global _OUTPUTS_NUMBERS_
	global _WATERING_PLAN_

	# Ask RTC to align the system date
	logging.debug('System datetime is %s' % time.strftime('%m/%d/%Y %H:%M:%S'))
	logging.debug('Aligning the system with the RTC datetime...')
	_datetime = getValue('datetime') # Returned format: MM/DD/YYYY hh:mm:ss
	if _datetime == None:
		logging.error("Onboard RTC doesn't respond")
		return False
	else:
		FNULL = open(os.devnull, 'w')
		# Needed format [[[[[YY]YY]MM]DD]hh]mm[.ss]
		_args = _datetime[6:10] + _datetime[0:2] + _datetime[3:5] + _datetime[11:13] + _datetime[14:16] + '.' + _datetime[17:19]
		subprocess.call(['date', '-s', _args], stdout=FNULL, stderr=subprocess.STDOUT)
			
	logging.debug("System datetime post alignment is %s" % time.strftime('%m/%d/%Y %H:%M:%S'))

	# Retrieve the outputs
	logging.info('Retrieving outputs...')
	retrieve_outputs()
	if _OUTPUTS_NUMBERS_ == 0:
		logging.warning('No actuator defined')
		return False

	# Retrieve the watering plan (only valid entries)
	logging.info('Retrieving watering plan...')
	retrieve_watering_plan()
	if len(_WATERING_PLAN_) == 0:
		logging.warning('No watering plan defined')
		return False
		
	# Startup procedure completed successfully
	return True

##################
# Main Loop procedure
#   Following the definition of all the functions used inside it
##################

#
# Get the status of the actuator as it is supposed to be
# according to the watering plan:
# 0 = inactive
# 1 = active
# 2 = active (is_forced = true)
#
def getExpectedStatus(_output):
	global _WATERING_PLAN_
	
	# Scroll the watering plan and check if this specific actuator is supposed to be activated now
	for wp in _WATERING_PLAN_:
	
		if wp['output'] == _output: # is the right actuator?
			_weekday = time.strftime('%w') # [0(Sunday),6]
			if _weekday == 0:
				_weekday == 7 # 1 (for Monday) through 7 (for Sunday)
				
			if str(wp['weekdays'])[(int(_weekday))-1] != '0': # is the right day of the week?
				# Calculate the number of minutes from midnight 
				_hour = int(time.strftime('%H')); _minute = int(time.strftime('%M'))
				_minutes = _hour * 60 + _minute;
				logging.debug('Minutes from midnight = %i (%i:%i)' % (_minutes,_hour,_minute))
				# Calculate the number of minutes from midnight of the record in watering plan
				logging.debug('StartTime %s, duration %i' % (str(wp['startTime']),int(wp['duration'])))
				_hour = int(str(wp['startTime'])[0:2]); _minute = int(str(wp['startTime'])[3:5])
				_minutesFrom = _hour * 60 + _minute;
				if (_minutes >= _minutesFrom and _minutes <= (_minutesFrom + int(wp['duration'])) ): # is the right time?
					logging.debug('Found a match in the watering plan:')
					logging.debug(' -> output = %i' % wp['output'])
					logging.debug(' -> weekday = %s (%s)' % (_weekday, wp['weekdays']))
					logging.debug(' -> time = %s and duration = %i' % (wp['startTime'], wp['duration']))
					if (wp['isForced'] == True or wp['isForced'] == 1):
						return 2
					else:
						return 1
	else:
		return 0

def evaluateTurningOnOutput(_output):
	if SOIL_MOISTURE_SENSOR == True:
		# TO BE IMPLAMENTED
		#
		# We have to consider the avegare value of the soil moisture
		# Consider that a sample is taken almost every seconds,
		# so 10 samples means the averege value in 10 seconds (rawly)
		#
		logging.debug('Soil moisture evaluation')
		if getLatestSoilMoistureAverageValue(10) > SOIL_MOISTURE_THRESHOLD:
			logging.debug('Soil moisture greater than threshold level: output will not be turned ON')
			return False

	if WEATHER_FORECAST == True:
		#
		# We have to consider the weather forecast
		#
		logging.debug('Weather forecast evaluation')
		
	# The output must be turned on
	return True

##################
# DB insert function 
##################
def store_output_status(_request):
	global _OUTPUTS_NUMBERS_
	global _DB_CON_
	
	_timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
	
	_outputRange = range(0,_OUTPUTS_NUMBERS_)
	rows = []
	for i in _outputRange:
		row = [_timestamp, i, _request[i]]
		rows.append(row)

	logging.debug('Trying to insert %i records' % len(rows))

	cur = _DB_CON_.cursor()
	cur.executemany('insert into outputs_log ([date], output, value) values (?,?,?)', rows )
	_DB_CON_.commit()


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
	_STAY_IN_THE_LOOP_ = startup()
	
	#
	# Main loop
	#
	if _STAY_IN_THE_LOOP_:
		logging.info('Running the main loop...')
	else:
		logging.warning('Nothing to do. Exiting...')
		
	while _STAY_IN_THE_LOOP_:
		
		# Reset output string to send to the sketch
		_request = ''
		
		_outputRange = range(0,_OUTPUTS_NUMBERS_)
		for _output in _outputRange:
			# Get the current status of the actuator
			_currentStatus = getCurrentStatus(_output)
    		
			# Check the expected status of this actuator
			_expectedStatus = getExpectedStatus(_output)
			
			logging.debug('Evaluating output %i (current status = %i, expected status = %i)' %(_output, _currentStatus, _expectedStatus))

			if _expectedStatus == 0:
				logging.debug('Turning OFF output %i ' % _output)
				_request += '0'
			elif _expectedStatus == 1:
				logging.debug('Considering to turn ON output %i' % _output)
				if evaluateTurningOnOutput(_output) == True:
					_request += '1'
				else:
					_request += '0'
			elif _expectedStatus == 2:
				logging.debug('Turning ON output %i [FORCED]' % _output)
				_request += '1'
			else:
				logging.warning('Unsupported expected status for output %i' % _output);
				logging.warning('The output will be turned off!')
				_request += '0'

		# Sending the request to change the output 			
		logging.info('Sending output request %s' % _request)
		_retValue = putValue('outputRequest', _request)
		if _retValue != _request:
			logging.error('putValue not working as expected')
		logging.debug('Sleeping for 2 seconds...')
		sleep(2)
		_returnValue = getValue('outputResponse')
		logging.debug('outputResponse = %s' % _returnValue)

		if _returnValue == _request:
			logging.debug('Output change request correctly processed')
			store_output_status(_request)
		else:
			logging.error("The output change request has not been correctly processed (request '%s', response '%s'" % (_request,_returnValue))
	
		# Wait 30 seconds 
		logging.debug('Sleep 30 seconds...')
		sleep(30)

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
		

