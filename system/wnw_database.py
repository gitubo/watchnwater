import sqlite3 as lite

ACTION_TURNOFF_DEFAULT = 0
ACTION_TURNON_AS_PER_WATERING_PLAN = 1
ACTION_TURNOFF_AS_PER_WATERING_PLAN = 2
ACTION_TURNON_FORCED = 3
ACTION_TURNOFF_FORCED = 4
ACTION_TURNON_AFTER_EVALUATION= 5
ACTION_TURNOFF_AFTER_EVALUATION = 6

DBERROR_INVALID_COUNT = -1

DB_FILENAME = '/mnt/sda1/wnw/wnwdb.sqlite'

class WnWDatabaseConnection:

	def __init__(self):
		self.dbConnection = None
		self.errorMessage = ''

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

	def close(self):
		self.errorMessage = ''
		if self.dbConnection:
			self.dbConnection.close()
			
	def getErrorMessage(self):
		return self.errorMessage
			
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
