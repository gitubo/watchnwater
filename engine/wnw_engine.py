#!/usr/bin/python

# Watch 'n' Water engine
#
# This is the core of the WnW project, where the logic of the watering system is implemented
# and the commands are actually sent to the sketch uploaded into the Arduino
#
# LICENSE: GPL v3

import sys
import os
import time
import logging
from time import sleep
import subprocess

#Import class to manage the database
import wnw_database as wnwDB

#Import class to manage the bridge
#(communication with the ATmega via mailbox)
import wnw_bridge as wnwBridge

#
#Constant definitions
#
#Log file name, absolute path
LOG_FILENAME = '/mnt/sda1/wnw/log/engine.log'
#Soil maisture sensor enabling
SOIL_MOISTURE_SENSOR = False
#Soil moisture threshold use 
#to decide if the soil is dry or wet
SOIL_MOISTURE_THRESHOLD = 1000
#Weather forecast feature enabling
WEATHER_FORECAST = False

#
#Global variables
#
#The connection to the database
theDB = None
#The connection to the bridge
theBridge = None
#A boolean to break the main loop in case of error
_STAY_IN_THE_LOOP_ = False
#The number of supported outputs
_OUTPUTS_NUMBER_ = 0
#The irrigation plan
_WATERING_PLAN_ = None


##################
# Startup procedure
#   Following the definition of the startup() procedure
#   and all the functions used inside it
##################

# Retrieve the outputs
def retrieveOutputs():
	global _OUTPUTS_NUMBER_
	global theDB

	_OUTPUTS_NUMBER_ = theDB.getOutputsNumber()
	if _OUTPUTS_NUMBER_ == wnwDB.DBERROR_INVALID_COUNT:
		logging.error('Invalid count of outputs coming from the DB (%s)' % theDB.getErrorMessage())
		_OUTPUT_NUMBERS_ = 0
	else:	
		logging.debug('Retrieved %i output(s)' % _OUTPUTS_NUMBER_)

# Retrieve watering plan
def retrieveWateringPlan():
	global _WATERING_PLAN_
	global theDB
	
	_WATERING_PLAN_ = theDB.getWateringPlan()
	if _WATERING_PLAN_ != None:
		logging.debug('Loaded %i record for the watering plan' % len(_WATERING_PLAN_))
	else:
		logging.error('Invalid watering plan coming from the DB (%s)' % theDB.getErrorMessage())
		_WATERING_PLAN_ = []			

# Store output current status 
def storeOutputStatus(_request):
	_timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
	
	if theDB.storeOutputStatus(_timestamp, _request) == True:
		logging.debug('Output status stored into the DB')	
		return True
	else:
		logging.error('Output status not saved into the DB (%s)' % theDB.getErrorMessage())
		return False

# Store sensors' values 
def storeSensorsValues(_temperature, _humidity, _pressure, _soilMoisture, _luminosity):
	_timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
	
	if theDB.storeSensorsValues(_timestamp, _temperature, _humidity, _pressure, _soilMoisture, _luminosity) == True:
		logging.debug('Sensors values stored into the DB')	
		return True
	else:
		logging.error('Sensors values not saved into the DB (%s)' % theDB.getErrorMessage())
		return False

# Store action related to the change of status of an output 
def storeAction(_output, _action):
	_timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
	
	if theDB.storeAction(_timestamp, _output, _action) == True:
		logging.debug('Action stored into the DB')	
		return True
	else:
		logging.error('Action not saved into the DB (%s)' % theDB.getErrorMessage())
		return False


# Startup procedure
def startup():
	global _OUTPUTS_NUMBER_
	global _WATERING_PLAN_

	# Ask RTC to align the system date
	logging.debug('System datetime is %s' % time.strftime('%m/%d/%Y %H:%M:%S'))
	logging.debug('Aligning the system with the RTC datetime...')
	_datetime = theBridge.getValue('datetime') # Returned format: MM/DD/YYYY hh:mm:ss
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
	retrieveOutputs()
	if _OUTPUTS_NUMBER_ == 0:
		logging.warning('No actuator defined')
		return False

	# Retrieve the watering plan (only valid entries)
	logging.info('Retrieving watering plan...')
	retrieveWateringPlan()
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
# Get the current status of the output:
# 0 = inactive
# 1 = active
#
def getCurrentStatus(_output):
	_in_loop = True
	_retVal = None
	
	while _in_loop == True:
		_retVal = theBridge.getValue('outputResponse')
		if _retVal == None: # value not found
			_in_loop = False
		elif len(_retVal) >= _output+1: #be sure the value is readable
			if (_retVal[_output] == '0' or _retVal[_output] == '1'): # value not ready
				_in_loop = False
		
	if _retVal == None:
		return None
	else:
		if _retVal[_output] == '0':
			return 0
		elif _retVal[_output] == '1':
			return 1
		else:
			logging.warning("Current status of output %d is not well defined ('%s') -> forced to LOW" %(_output,_retVal[_output]))
			return 0
	return None

#
# Get the current status of all the outputs:
# 0 = inactive
# 1 = active
#
def waitForOutputsResponse(_output_number):
	_in_loop = True
	_retVal = None
	
	while _in_loop == True:
		_retVal = theBridge.getValue('outputResponse')
		if _retVal == None: # value not found
			_in_loop = False
		elif len(_retVal) == _output_number: #be sure the value is readable
			_counter = 0;
			for x in range(0, _output_number):
				if (_retVal[x] == '0' or _retVal[x] == '1'): # value ready
					_counter += 1
			if _counter == _output_number:
				_in_loop = False
		else:
			logging.error("The expected number of output (%i) differs from the response (%i, '%s')" %(_output_number,len(_retVal)))
			_retVal = None
			_in_loop = False
		
	if _retVal == None:
		return None
	else:
		return _retVal

#
# Get the status of the actuator as it is supposed to be
# according to the watering plan:
# 0 = inactive
# 1 = active
# 2 = active (is_forced = true)
#
def getExpectedStatus(_output, _nowInSeconds):
	global _WATERING_PLAN_
	
	# Scroll the watering plan and check if this specific actuator is supposed to be activated now
	for wp in _WATERING_PLAN_:
	
		if wp['output'] == _output: # is the right actuator?
			_weekday = time.strftime('%w') # [0(Sunday),6]
			if _weekday == 0:
				_weekday == 7 # 1 (for Monday) through 7 (for Sunday)
				
			if str(wp['weekdays'])[(int(_weekday))-1] != '0': # is the right day of the week?	
				# Calculate the number of minutes from midnight of the record in watering plan
				logging.debug('StartTime %s, duration %i' % (str(wp['startTime']),int(wp['duration'])))
				_hour = int(str(wp['startTime'])[0:2]); _minute = int(str(wp['startTime'])[3:5])
				_secondsFrom = _hour * 3600 + _minute * 60;
				logging.debug('_nowInSeconds=%i, _secondsFrom=%i, secondsTo=%i' % (_nowInSeconds, _secondsFrom, (_secondsFrom + int(wp['duration']) * 60)))
				if (_nowInSeconds >= _secondsFrom and _nowInSeconds <= (_secondsFrom + int(wp['duration']) * 60) ): # is the right time?
					logging.debug('Found a match in the watering plan:')
					logging.debug(' -> output = %i' % wp['output'])
					logging.debug(' -> weekday = %s (%s)' % (_weekday, wp['weekdays']))
					logging.debug(' -> time = %s and duration = %i' % (wp['startTime'], wp['duration']))
					if (wp['isForced'] == True or wp['isForced'] == 1):
						return 2
					else:
						return 1
	return 0

def evaluateTurningOnOutput(_output):
	if SOIL_MOISTURE_SENSOR == True:
		# TO BE IMPLEMENTED
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
	theDB = wnwDB.WnWDatabaseConnection()
	logging.info('Connecting to the DB...')
	if theDB.init():
		logging.info('DB connection established')
	else:
		logging.error('DB connection not established: %s' % theDB.getErrorMessage())
		
	theBridge = wnwBridge.WnWBridge()
	logging.info('Connecting to the Bridge...')
	if theBridge.init():
		logging.info('Bridge connection established')
	else:
		logging.error('Bridge connection not established: %s' % theBridge.getErrorMessage())
	
	#
	# Startup process
	#
	logging.info('Calling the startup procedure...')
	_STAY_IN_THE_LOOP_ = startup()
	# _lastStartTime variable is used
	# to store the sensors values every 60 seconds
	_lastStartTime = 0; 
	# _lastSavedReturnStatus is used 
	# to store the status of the outputs
	# only if it differs from the previously saved status
	_lastSavedReturnStatus = '';
	
	#
	# Main loop
	#
	if _STAY_IN_THE_LOOP_:
		logging.info('Running the main loop...')
	else:
		logging.warning('Nothing to do. Exiting...')
		
	while _STAY_IN_THE_LOOP_:
	
		# Get time to calculate loop duration in milliseconds
		_loopStartTime = int(time.time() * 1000)
		
		# Store output status every one minute
		logging.info('Setup running? ' + theBridge.getValue('isSetupRunning'))
		if (int(_loopStartTime/60000) != _lastStartTime and theBridge.getValue('isSetupRunning') == False):
			storeSensorsValues(theBridge.getValue('temperature'), theBridge.getValue('humidity'), theBridge.getValue('pressure'), theBridge.getValue('soilMoisture'), theBridge.getValue('luminosity'))
			_lastStartTime = int(_loopStartTime/60000)
			
		# Calculate the number of minutes from midnight 
		_cHour = time.strftime('%H'); _cMinutes = time.strftime('%M'); _cSeconds = time.strftime('%S')
		_nowInSeconds = int(_cHour) * 3600 + int(_cMinutes) * 60 + int(_cSeconds);	
		logging.debug('Current time is %s:%s:%s' % (_cHour,_cMinutes,_cSeconds))
	
		# Reset output string to send to the sketch
		_request = ''
		
		_outputRange = range(0,_OUTPUTS_NUMBER_)
		for _output in _outputRange:
			logging.debug('Evaluating output %i' % _output)
			# Get the current status of the actuator
			_currentStatus = getCurrentStatus(_output)
    		
			# Check the expected status of this actuator
			_expectedStatus = getExpectedStatus(_output, _nowInSeconds)
			
			logging.debug('Current status = %i => expected status = %i' %(_currentStatus, _expectedStatus))

			if _expectedStatus == 0:
				if _currentStatus != 0:
					logging.debug('Turning OFF output %i ' % _output)
					storeAction(_output, wnwDB.ACTION_TURNOFF_AS_PER_WATERING_PLAN)
				_request += '0'
			elif _expectedStatus == 1:
				if evaluateTurningOnOutput(_output) == True:
					if _currentStatus != 1:
						logging.debug('Turning ON output %i ' % _output)
						storeAction(_output, wnwDB.ACTION_TURNON_AS_PER_WATERING_PLAN)
					_request += '1'
				else:
					if _currentStatus != 0:
						logging.debug('Turning OFF output %i ' % _output)
						storeAction(_output, wnwDB.ACTION_TURNOFF_AFTER_EVALUATION)
					_request += '0'
			elif _expectedStatus == 2:
				if _currentStatus != 1:
					logging.debug('Turning ON output %i [FORCED]' % _output)
					storeAction(_output, wnwDB.ACTION_TURNON_FORCED)
				_request += '1'
			else:
				logging.warning('Unsupported expected status for output %i' % _output);
				logging.warning('The output will be turned off!')
				storeAction(_output, wnwDB.ACTION_TURNOFF_DEFAULT)
				_request += '0'

		# Sending the request to change the output 			
		logging.debug('Sending output request %s' % _request)
		_retValue = theBridge.putValue('outputRequest', _request)
		if _retValue != _request:
			logging.error('putValue not working as expected')

		_returnValue = waitForOutputsResponse(_OUTPUTS_NUMBER_)
		if _returnValue == None:
			logging.error('Output response not present')
		else:
			logging.debug('outputResponse = %s' % _returnValue)

			if _returnValue == _request:
				logging.debug('Output change request correctly processed')
			else:
				logging.error("The output change request has not been correctly processed (request '%s', response '%s'" % (_request,_returnValue))
		
			# Store output status only if it differs from the previous status
			if _returnValue != _lastSavedReturnStatus:
				storeOutputStatus(_returnValue)
				_lastSavedReturnStatus = _returnValue
	
		# Get time to calculate loop duration
		logging.debug('Sleep...')
		_loopStopTime = int(time.time() * 1000)
		_durationInMillis = _loopStopTime - _loopStartTime
		_to10Seconds = (10000.0 - float(_durationInMillis)) / 1000.0
		if _to10Seconds > 0.0:
			sleep(_to10Seconds)

except Exception as e:

	logging.error("Exception %s:" % e.args[0])
	sys.exit(1)

finally:

	if theDB:
		logging.info('Closing DB connection...')
		theDB.close()
	if theBridge:
		logging.info('Closing BRIDGE connection...')
		theBridge.close()
		

