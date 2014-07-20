#include <Wire.h>
#include <Process.h>
#include <Console.h>
#include <DHT.h>
#include <RTClib.h>
#include <Adafruit_BMP085.h>

//Definition of the pin connected to the DHT sensor
#define DHTPIN 8    
#define DHTTYPE DHT22 

//Definition of the pin connected to the soil moisture sensor
#define SOILMOISTUREPIN 14 

//Definition of the general purpose led used tfor diagnosys
#define LEDPIN 13

//Definition of the pin connected to the output
const int outputPin[] = {4, 5, 6, 7};

//Definition of the global variables
DHT dht(DHTPIN, DHTTYPE);
RTC_DS1307 rtc;
Adafruit_BMP085 bmp;

void setup() {
  // Prepare the onboard led to blink once and then stay on 
  // until the setup procedure is complete
  pinMode(LEDPIN, OUTPUT);
  digitalWrite(LEDPIN, HIGH);
  
  // initialize serial communication:
  logBegin();
  Bridge.begin();
  log("You're connected");

  // initialize I2C
  log("Initializing I2C...");
  Wire.begin();

  // initialize RealTimeClock module
  log("Communicating with RTC...");
  rtc.begin();
  if (! rtc.isrunning()) {
    log("ERROR: RTC does not work correctly.");
    //rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  }

  // initialize DHT sensor
  log("Communicating with DHT...");
  dht.begin();

  // initialize BMP180 module
  log("Communicating with BMP...");
  if (!bmp.begin()) {
	 log("ERROR: BMP180 does not work correctly.");
  }

  /*
   * Publish current output status through the mailbox
   * "outputResponse" is the key used to inform about 
   * the status of the ouput
   */
  int outputsNumber = sizeof(outputPin) / sizeof(int);
  for(int i=0; i < outputsNumber; i++) {
    pinMode(outputPin[i], OUTPUT);
    digitalWrite(outputPin[i], LOW);
  }
  String _response = "";
  for(int i=0; i < outputsNumber; i++)
    _response += String(digitalRead(outputPin[i]));
  Bridge.put("outputResponse", _response);
  for(int i=0; i < outputsNumber; i++) {
    log("Output Pin "+String(outputPin[i]) + " value " + String(digitalRead(outputPin[i])));
  }
  
  // Setup procedure complete
  digitalWrite(LEDPIN, LOW);
}

void loop() {
  digitalWrite(LEDPIN, HIGH);

  // Read timestamp
  DateTime now = rtc.now();
  String _timestamp = formattedDateTime(now);
  log("Timestamp: " + _timestamp); 

  /* 
   * Always provide the status of the outputs
   */
  int outputsNumber = sizeof(outputPin) / sizeof(int);
  String _response = "";
  for(int i=0; i < outputsNumber; i++)
    _response += "x";
  Bridge.put("outputResponse", _response);
  
  /*
   * Check if there is a request to change the
   * status of an output and in case change it.
   * "outputRequest" is the key used to receive 
   * the request to change the status of the outputs.
   * '0' means LOW, anything different from '0' means HIGH
   */
  char* _output;
  _output = (char *) malloc(outputsNumber);
  String key = "outputRequest";
  if(Bridge.get(key.c_str(), _output, outputsNumber) != 0) {
    for(int i=0; i < outputsNumber; i++){
      if (_output[i]=='0')
        digitalWrite(outputPin[i], LOW);
      else
        digitalWrite(outputPin[i], HIGH);
      log("Output Pin "+String(outputPin[i]) + " value " + _output[i]);
    }
  }
  free(_output);
  _response = "";
  for(int i=0; i < outputsNumber; i++)
    _response += String(digitalRead(outputPin[i]));
  Bridge.put("outputResponse", _response);
  log("Output: " + _response); 
  
  // Read humidity and temperature from dht sensor
  int _humidity = (int)(dht.readHumidity()*100.0);
  int _temperature = (int)(dht.readTemperature()*100.0);
  if (isnan(_humidity) || isnan(_temperature)) {
    log("ERROR: Failed to read from DHT sensor");
  } else {
    log("Data from DHT sensor: "); 
    log(" -> Humidity: " + String(((float)_humidity)/100.0) + "%"); 
    log(" -> Temperature: " + String(((float)_temperature)/100.0) + "*C"); 
  }
  
  // Read pressure and temperature from bmp180 module
  unsigned long _pressure = bmp.readPressure();
  int _temperatureBMP = (int)(bmp.readTemperature()*100.0);
  if (isnan(_pressure) || isnan(_temperatureBMP)) {
    log("Failed to read from BMP180 module");
  } else {
    log("Data from BMP180 module: "); 
    log(" -> Pressure: " + String((float)(_pressure)/100.0) + "Pa"); 
    log(" -> Temperature: " + String(((float)_temperatureBMP)/100.0) + "*C");
  }

  // Read the soil moisture level
  int _soilMoisture = analogRead(SOILMOISTUREPIN);
  
  /*
   * Make sensors info available to outside
   * using the mailbox
   */
  Bridge.put(String("datetime"), _timestamp);
  Bridge.put(String("timestamp"), _timestamp);
  Bridge.put(String("temperature"), String(_temperature));
  Bridge.put(String("humidity"), String(_humidity));
  Bridge.put(String("pressure"), String(_pressure));
  Bridge.put(String("soil_moisture"), String(_soilMoisture));
  Bridge.put(String("luminosity"), String(""));

  // Turn off the led to indicate the cycle has been completed
  digitalWrite(LEDPIN, LOW);
  
  //Wait one second
  delay(1000);
}

/*
 * Gets the datetime and return a well formatted string
 * according to the format: MM/DD/YYYY HH:MM:SS
 */
String formattedDateTime(DateTime now){
  String retval = "";
  retval += print2Char(now.month()) + "/";
  retval += print2Char(now.day()) + "/";
  if(now.year()>99) retval += now.year();
  else retval += "20" + now.year();
  retval += " ";
  retval += print2Char(now.hour()) + ":";
  retval += print2Char(now.minute()) + ":";
  retval += print2Char(now.second());
  return retval;
}

/*
 * Gets an integer and returns a 2 digit string
 * in case the integer is less than 10.
 * To be used to format hours, minutes, seconds,
 * month and day in datetime string
 */
String print2Char(int value){
  if(value<10) return "0"+String(value);
  return String(value);  
}

void logBegin(){
  if(0) {
    Serial.begin(9600);
    while (!Serial);
  }  
}

void log(String message){
  if(0) Serial.println(message);
}
