-- Table: settings
CREATE TABLE settings (
    name                VARCHAR( 16 )  PRIMARY KEY,
    int_value           INTEGER,
    string_value        VARCHAR( 64 ),
    change_date         DATETIME       DEFAULT ( datetime( CURRENT_TIMESTAMP, 'localtime')  )
);

-- Index: idx_settings
CREATE INDEX idx_settings ON settings (
    name
);

INSERT INTO [settings] ([name], [int_value]) VALUES ('outputs_number', 4);

-- Table: sensors_log
CREATE TABLE sensors_log (
    id                  INTEGER          PRIMARY KEY AUTOINCREMENT,
    [date]              DATETIME         DEFAULT ( datetime( CURRENT_TIMESTAMP, 'localtime' )  ),
    temperature         INTEGER,
    humidity            INTEGER,
    pressure            INTEGER,
    soil_moisture       INTEGER,
    luminosity          INTEGER
);

-- Index: idx_sensors_log
CREATE INDEX idx_sensors_log ON sensors_log (
    [date]
);

-- Trigger: sensors_log_limit
CREATE TRIGGER sensors_log_limit AFTER INSERT ON sensors_log WHEN NEW.rowid % 1000 = 0 BEGIN
	DELETE FROM sensors_log WHERE [date] < (SELECT MIN([date]) FROM sensors_log ORDER BY [date] DESC LIMIT 10000);
END;

-- Table: outputs
CREATE TABLE outputs (
    id                  INTEGER         PRIMARY KEY AUTOINCREMENT,
    description         VARCHAR( 150 )  NOT NULL UNIQUE
);

INSERT INTO [outputs] ([id], [description]) VALUES (0, 'Zone A');
INSERT INTO [outputs] ([id], [description]) VALUES (1, 'Zone B');
INSERT INTO [outputs] ([id], [description]) VALUES (2, 'Zone C');
INSERT INTO [outputs] ([id], [description]) VALUES (3, 'Zone D');

-- Table: outputs_log
CREATE TABLE outputs_log (
    [date]	            DATETIME         DEFAULT ( datetime( CURRENT_TIMESTAMP, 'localtime' )  ),
    output              VARCHAR( 32 )    NOT NULL
);

-- Index: idx_outputs_log
CREATE INDEX idx_outputs_log ON outputs_log (
    [date]
);

-- Trigger: outputs_log_limit
CREATE TRIGGER outputs_log_limit AFTER INSERT ON outputs_log WHEN NEW.rowid % 1000 = 0 BEGIN
	DELETE FROM outputs_log WHERE [date] < (SELECT MIN([date]) FROM outputs_log ORDER BY [date] DESC LIMIT 10000);
END;

-- Table: actions
CREATE TABLE actions (
    id                  INTEGER          PRIMARY KEY AUTOINCREMENT,
    description         VARCHAR( 128 )   NOT NULL
);

INSERT INTO [actions] ([id], [description]) VALUES ( 1, 'Turned OFF as default choise');
INSERT INTO [actions] ([id], [description]) VALUES (10, 'Turned ON as per watering plan');
INSERT INTO [actions] ([id], [description]) VALUES (11, 'Turned OFF as per watering plan');
INSERT INTO [actions] ([id], [description]) VALUES (20, 'Turned ON [forced]');
INSERT INTO [actions] ([id], [description]) VALUES (21, 'Turned OFF [forced]');
INSERT INTO [actions] ([id], [description]) VALUES (30, 'Turned ON after evaluation');
INSERT INTO [actions] ([id], [description]) VALUES (31, 'Turned OFF after soil moisture evaluation');
INSERT INTO [actions] ([id], [description]) VALUES (32, 'Turned OFF after weather evaluation');
INSERT INTO [actions] ([id], [description]) VALUES (33, 'Turned OFF after evaluation');
INSERT INTO [actions] ([id], [description]) VALUES (34, 'Keep OFF after soil moisture evaluation');
INSERT INTO [actions] ([id], [description]) VALUES (35, 'Keep OFF after weather evaluation');
INSERT INTO [actions] ([id], [description]) VALUES (36, 'Keep OFF after evaluation');

-- Table: actions_log
CREATE TABLE actions_log (
    id                  INTEGER          PRIMARY KEY AUTOINCREMENT,
    [date]              DATETIME         DEFAULT ( datetime( CURRENT_TIMESTAMP, 'localtime' )  ),
    output              INTEGER          REFERENCES outputs ( id ) NOT NULL,
    [action]            INTEGER          REFERENCES actions ( id ) NOT NULL
);

-- Index: idx_actions_log
CREATE INDEX idx_actions_log ON actions_log (
    [date]
);

-- Trigger: actions_log_limit
CREATE TRIGGER actions_log_limit AFTER INSERT ON actions_log WHEN NEW.rowid % 1000 = 0 BEGIN
	DELETE FROM actions_log WHERE [date] < (SELECT MIN([date]) FROM actions_log ORDER BY [date] DESC LIMIT 10000);
END;

-- Table: watering_plan
CREATE TABLE watering_plan (
    id                  INTEGER          PRIMARY KEY AUTOINCREMENT,
    output              INTEGER          REFERENCES outputs ( id ) NOT NULL,
    [from]              DATETIME         NOT NULL,
    duration            INTEGER          NOT NULL,
    weekdays_bitmask    VARCHAR( 8 )     DEFAULT ('11111110') NOT NULL,
    is_valid            BOOLEAN          DEFAULT ( 1 ) NOT NULL,
    is_oneshot          BOOLEAN          DEFAULT ( 0 ) NOT NULL,
    is_forced           BOOLEAN          DEFAULT ( 0 ) NOT NULL
);

INSERT INTO [watering_plan] ([output], [from], [duration]) VALUES (0, '2014-01-01 07:00:00', 5);
INSERT INTO [watering_plan] ([output], [from], [duration], [weekdays_bitmask]) VALUES (0, '2014-01-01 19:00:00', 5, '10101010');
INSERT INTO [watering_plan] ([output], [from], [duration]) VALUES (1, '2014-01-01 07:05:00', 5);
INSERT INTO [watering_plan] ([output], [from], [duration]) VALUES (2, '2014-01-01 07:10:00', 5);
INSERT INTO [watering_plan] ([output], [from], [duration]) VALUES (3, '2014-01-01 07:15:00', 5);
INSERT INTO [watering_plan] ([output], [from], [duration], [weekdays_bitmask]) VALUES (3, '2014-01-01 19:05:00', 10, '10101010');
