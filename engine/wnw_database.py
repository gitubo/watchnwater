#Import sqlite3 library
import sqlite3 as lite
from datetime import datetime, timedelta

#Definition of action types
ACTION_TURNOFF_DEFAULT = 1
ACTION_TURNON_AS_PER_WATERING_PLAN = 10 
ACTION_TURNOFF_AS_PER_WATERING_PLAN = 11 
ACTION_TURNON_FORCED = 20 
ACTION_TURNOFF_FORCED = 21 
ACTION_TURNON_AFTER_EVALUATION= 30 
ACTION_TURNOFF_AFTER_SOIL_MOISTURE_EVALUATION = 31 
ACTION_TURNOFF_AFTER_WEATHER_EVALUATION = 32
ACTION_TURNOFF_AFTER_EVALUATION = 33
ACTION_KEEPOFF_AFTER_SOIL_MOISTURE_EVALUATION = 34
ACTION_KEEPOFF_AFTER_WEATHER_EVALUATION = 35
ACTION_KEEPOFF_AFTER_EVALUATION = 36


#Definition of sensor types
SENSOR_TYPE_TEMPERATURE = 0
SENSOR_TYPE_HUMIDITY = 1
SENSOR_TYPE_PRESSURE = 2
SENSOR_TYPE_SOIL_MOISTURE = 3
SENSOR_TYPE_LUMINOSITY = 4

#Return code to be used in case of invalid counter
DBERROR_INVALID_COUNT = -1

#Database filename
DB_FILENAME = '/mnt/sda1/wnw/wnwdb.sqlite'

#
# The Database connection class
#
class WnWDatabaseConnection:

	#The constructor initializing 
	#the connection and the error message 
	def __init__(self):
		self.dbConnection = None
		self.errorMessage = ''

	# The init function used to
	# setup the connection manually
	def init(self):
		self.errorMessage = ''
		if not self.dbConnection:
			self.dbConnection = lite.connect(DB_FILENAME)
			if not self.dbConnection:
				self.errorMessage = 'Database not accessible.'				
				return False
			else:
				return True
		else:
			self.errorMessage = 'Database connection already established'
			return False

	# Close the connection and reset the error message
	def close(self):
		self.errorMessage = ''
		if self.dbConnection:
			self.dbConnection.close()
	
	# Return the error message if any
	def getErrorMessage(self):
		return self.errorMessage
	
	# Return the number of output defined 
	# as system parameter into the database	
	def getOutputsNumber(self):
		self.errorMessage = ''
		try:
			cur = self.dbConnection.cursor()
			cur.execute('SELECT count(id) FROM outputs')
			count = cur.fetchone()[0]
			return count
		except Exception as error:
			self.errorMessage = 'SQLite3 execution exception: ' + str(error)
			return DBERROR_INVALID_COUNT
			
	# Return the watering plan as an array
	# Every item of the array is defined as follow:
	# The ID of the output: integer
	# HH:MM representing the time (hour and minutes) when the watering must be started: string
	# The duration in minutes of the watering: integer
	# The bitmask of the days of the week when the watering must be started ('0' means do NOT start, '1' means start): string (only the most important 7 charachers will be considered
	# The information if the watering must be done a part from any other condition (soil moisture or weather forecast): integer (0 means not forced, 1 or any other values means forced)
	def getWateringPlan(self):
		self.errorMessage = ''
		try:
			_retArray = []
			cur = self.dbConnection.cursor()
			query = "SELECT id, output, strftime('%H:%M', [from]) as start_time, duration, weekdays_bitmask, is_forced"
			query += " FROM watering_plan"
			query += " WHERE is_valid = 1"	
			cur.execute(query)
			rows = cur.fetchall()

			for row in rows:
				item = {"output":row[1], "startTime":row[2], "duration":row[3], "weekdays":row[4], "isForced":row[5]}
				_retArray.append(item)	
			
			return _retArray
		except Exception as error:
			self.errorMessage = 'SQLite3 execution exception: ' + str(error)
			return None
	
	def getLatestSensorsValues(self, _sensorType, _numberOfValues):
		self.errorMEssage = ''
		try:
			_retArray = []
			cur = self.dbConnection.cursor()
			cur.execute('SELECT temperature, humidity, pressure, soil_moisture, luminosity FROM sensors_log ORDER BY [date] desc LIMIT ?', (str(_numberOfValues),))
			rows = cur.fetchall()
			if (_sensorType < 0 or _sensorType > 4):
				self.errorMessage = "Invalid sensor type passed";
				return None
			for row in rows:
				_retArray.append(row[_sensorType]/100)
			return _retArray			
		except Exception as error:
			self.errorMessage = 'SQLite3 execution exception: ' + str(error)
			return None
	
	# Store the value of the outputs
	# _timestamp: the datetime when the output has been read
	# _status: a string of '0' or '1' encoding the status of the outputs
	# Returns True if the transaction has been committed, otherwise False 
	def storeOutputStatus(self, _timestamp, _status):
		self.errorMessage = ''
		try:
			cur = self.dbConnection.cursor()
			cur.execute('insert into outputs_log ([date], output) values (?,?)', (_timestamp, _status) )
			self.dbConnection.commit()
			return True
		except Exception as error:
			self.errorMessage = 'SQLite3 execution exception: ' + str(error)
			return False

	# Store the value of the sensors
	# _timestamp: the datetime when the sensors have been read
	# _temperature: an integer storing the value of the temperature 
	# _humidity: an integer storing the value of the humidity 
	# _pressure: an integer storing the value of the pressure
	# _soilMoisture: an integer storing the value of the soil moisture
	# _luminosity: an integer storing the value of the luminosity
	# Please note that the last 2 digit of the integer are used to store the decimal part (xy.zw is stored as xyzw)
	# Returns True if the transaction has been committed, otherwise False 
	def storeSensorsValues(self, _timestamp, _temperature, _humidity, _pressure, _soilMoisture, _luminosity):
		self.errorMessage = ''
		try:
			cur = self.dbConnection.cursor()
			cur.execute('insert into sensors_log ([date], temperature, humidity, pressure, soil_moisture, luminosity) values (?,?,?,?,?,?)', (_timestamp, _temperature, _humidity, _pressure, _soilMoisture, _luminosity) )
			self.dbConnection.commit()
			return True
		except Exception as error:
			self.errorMessage = 'SQLite3 execution exception: ' + str(error)
			return False

	# Store an action operated on an output
	# _timestamp: the datetime when the action has been performed
	# _output: the impacted output
	# _actionid: the type of action
	# Returns True if the transaction has been committed, otherwise False 
	def storeAction(self, _timestamp, _output, _actionid):
		self.errorMessage = ''
		try:
			cur = self.dbConnection.cursor()
			cur.execute('insert into actions_log ([date], output, [action]) values (?,?,?)', (_timestamp, _output, _actionid) )
			self.dbConnection.commit()
			return True
		except Exception as error:
			self.errorMessage = 'SQLite3 execution exception: ' + str(error)
			return False


	def getLatestPressureDifferences(self):
		self.errorMessage = ''
		try:
			retValues = []
			dateTo = datetime.now()  
			dateFrom = dateTo - timedelta(minutes=10)
			cur = self.dbConnection.cursor()
			_currentPressure = 0
			for x in range(0, 4):
				sql = "select pressure from sensors_log where [date] between datetime('" + dateFrom.strftime("%Y-%m-%d %H:%M:%S") + "') and datetime('" + dateTo.strftime("%Y-%m-%d %H:%M:%S") + "') limit 10"
				cur.execute(sql)
				_samples = 0
				pressure = 0
				for row in cur:
					pressure += row[0]
					_samples += 1
				if _samples > 0:
					pressure /= _samples
				else:
					pressure = None
				if x != 0:
					if (_currentPressure != None and pressure != None):
						retValues.append(_currentPressure - pressure)
					else:
						retValues.append(null)
				else:
					_currentPressure = pressure				
				dateTo = dateTo - timedelta(hours=1)
				dateFrom = dateTo - timedelta(minutes=10)
				
			return retValues				
			
		except Exception as error:
			self.errorMessage = 'SQLite3 execution exception: ' + str(error)
			return None