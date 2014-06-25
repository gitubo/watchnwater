

class DatabaseConnection:
    
    _DB_CON_ = None

	i = 12345
	
	def initDB():
		if not _DB_CON_:
			logging.info('Connecting to the DB (%s)...' % DB_FILENAME)
			_DB_CON_ = lite.connect(DB_FILENAME)
			if not _DB_CON_:
				logging.error('Database not accessible. Exiting...')
				return _DBERROR_NOT_ACCESSIBLE_
			else:
				logging.info('Database connection established.')
				return _DBSUCCESS_CONNECTION_ESTABLISHED
		else:
			logging.warning('Database connection already established')
			return _DBERROR_CONNECTION_NOT_ESTABLISHED
