import sqlite3 as lite

DBSUCCESS = 0

DBERROR_NOT_ACCESSIBLE = 1000
DBERROR_CONNECTION_NOT_ESTABILISHED = 1100

DBERROR_EXCEPTION = 2000

DBERROR_INVALID_COUNT = -1

DB_FILENAME = '/mnt/sda1/wnw/wnwdb.sqlite'

class WnWDatabaseConnection:

	def __init__(self):
		self.dbConnection = None
		self.returnMessage = ''

	def init(self):
		self.returnMessage = ''
		if not self.dbConnection:
			self.dbConnection = lite.connect(DB_FILENAME)
			if not self.dbConnection:
				self.returnMessage = 'Database not accessible.'				
				return False
			else:
				self.returnMessage = 'Database connection established.'
				return True
		else:
			self.returnMessage = 'Database connection already established'
			return False

	def close(self):
		self.returnMessage = ''
		if self.dbConnection:
			self.dbConnection.close()
			
	def getReturnMessage(self):
		return self.returnMessage
			
	def getOutputsNumber(self):
		self.returnMessage = ''
		try:
			cur = self.dbConnection.cursor()
			cur.execute('SELECT count(id) FROM outputs')
			count = cur.fetchone()[0]
			return count
		except Exception as error:
			self.returnMessage = 'SQLite3 execution exception: ' + str(error)
			return DBERROR_INVALID_COUNT
			
	def getWateringPlan(self):
		self.returnMessage = ''
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
			self.returnMessage = 'SQLite3 execution exception: ' + str(error)
			return None
			
	def putOutputStatus(self, _status):
		self.returnMessage = ''
		try:
			cur = self.dbConnection.cursor()
			cur.executemany('insert into outputs_log ([date], output, value) values (?,?,?)', _status )
			self.dbConnection.commit()
			return True
		except Exception as error:
			self.returnMessage = 'SQLite3 execution exception: ' + str(error)
			return False
	