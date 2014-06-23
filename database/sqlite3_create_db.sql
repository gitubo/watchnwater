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
    temperature         NUMERIC( 4, 2 ),
    humidity            NUMERIC( 2, 0 ),
    pressure            NUMERIC( 6, 2 ),
    soil_moisture       NUMERIC( 2, 0 ),
    luminosity          NUMERIC( 6, 0 )
);

-- Table: outputs
CREATE TABLE outputs (
    id                  INTEGER         PRIMARY KEY AUTOINCREMENT,
    sketch_name         VARCHAR( 150 )  NOT NULL UNIQUE
);

INSERT INTO [outputs] ([id], [sketch_name]) VALUES (1, 'OUTPUT1');
INSERT INTO [outputs] ([id], [sketch_name]) VALUES (2, 'OUTPUT2');
INSERT INTO [outputs] ([id], [sketch_name]) VALUES (3, 'OUTPUT3');
INSERT INTO [outputs] ([id], [sketch_name]) VALUES (4, 'OUTPUT4');

-- Table: outputs_log
CREATE TABLE outputs_log (
    id                  INTEGER          PRIMARY KEY AUTOINCREMENT,
    [date]	            DATETIME         DEFAULT ( datetime( CURRENT_TIMESTAMP, 'localtime' )  ),
    output              INTEGER          REFERENCES outputs ( id ) NOT NULL,
    boolean_value       BOOLEAN,
    int_value           INTEGER,
    string_value        VARCHAR( 64 )
);

-- Index: idx_outputs_log
CREATE INDEX idx_outputs_log ON outputs_log (
    output
);

-- Table: actions
CREATE TABLE actions (
    id                  INTEGER          PRIMARY KEY AUTOINCREMENT,
    description         VARCHAR( 128 )   NOT NULL
);

INSERT INTO [actions] ([id], [description]) VALUES (1, 'Turned ON as per watering plan');
INSERT INTO [actions] ([id], [description]) VALUES (2, 'Turned OFF as per watering plan');
INSERT INTO [actions] ([id], [description]) VALUES (3, 'Turned ON [forced]');
INSERT INTO [actions] ([id], [description]) VALUES (4, 'Turned OFF [forced]');

-- Table: actions_log
CREATE TABLE actions_log (
    id                  INTEGER          PRIMARY KEY AUTOINCREMENT,
    [date]              DATETIME         DEFAULT ( datetime( CURRENT_TIMESTAMP, 'localtime' )  ),
    [action]            INTEGER          REFERENCES actions ( id ) NOT NULL,
    [from]              DATETIME,
    [to]                DATETIME,
    note                VARCHAR( 128 )
);

-- Table: watering_plan
CREATE TABLE watering_plan (
    id                  INTEGER          PRIMARY KEY AUTOINCREMENT,
    output              INTEGER          REFERENCES outputs ( id ) NOT NULL,
    [from]              DATETIME         NOT NULL,
    duration            INTEGER          NOT NULL,
    weekdays_bitmask    VARCHAR( 8 )     DEFAULT ('01111111') NOT NULL,
    is_valid            BOOLEAN          DEFAULT ( 1 ) NOT NULL,
    is_forced           BOOLEAN          DEFAULT ( 0 ) NOT NULL
);
