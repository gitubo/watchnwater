#include <Wire.h>
#include <Console.h>
#include <DHT.h>
#include <RTClib.h>
#include <Adafruit_BMP085.h>
#include <TSL2561.h>

/*
 * Define the duration of the main loop
 * in microseconds. 1 second is 1.000.000 microseconds
 * _previousMicros is a global variable used
 * to control cycle duration without delay function
 */
#define CYCLEDURATION 1000000
unsigned long _previousMicros = 0;
 
//Definition of the pin connected to the DHT sensor
#define DHTPIN 8    
#define DHTTYPE DHT22 

//Definition of the pin connected to the soil moisture sensor
#define SOILMOISTUREPIN A0

//Definition of the general purpose led used for diagnosys
#define LEDPIN 13

//Define if we are in DEBUG mode (1) or not (0)
#define DEBUG 0

//Definition of the pin connected to the output
const int outputPin[] = {4, 5, 6, 7};
unsigned int _outputsNumber = 0;

//Definition of the global variables
DHT dht(DHTPIN, DHTTYPE);
RTC_DS1307 rtc;
Adafruit_BMP085 bmp;
TSL2561 tsl(TSL2561_ADDR_FLOAT); 

/*
 * Definition of global variables used to 
 * store values of the sensors
 */
float _humidity = 0.0;
float _temperature = 0.0;
unsigned long _pressure = 0;
int _soilMoisture = 0;
uint16_t _ir, _full;

/*
 * Global variable used to count the
 * number of samples of the sensors.
 * Needed to calculate the average values
 */
int _samplesNumber = 0;

/* 
 * Define a set of boolean to trace if 
 * a sensor/input is present and if
 * it works fine
 */
bool isRealTimeClockWorking = false;
bool isTemperatureSensorWorking = false;
bool isHumiditySensorWorking = false;
bool isPressureSensorWorking = false;
bool isSoilMoistureSensorWorking = false;
bool isLuminositySensorWorking = false;

void setup() {
  // Inform we are in the setup function
  Bridge.put(String("isSetupRunning"), String(true));

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
    isRealTimeClockWorking = false;
    log("ERROR: RTC does not work correctly.");
    //rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  } else {
    isRealTimeClockWorking = true;
  }

  // initialize DHT sensor
  log("Communicating with DHT...");
  dht.begin();
  if (isnan(dht.readTemperature())) {
    isTemperatureSensorWorking = false;
    isHumiditySensorWorking = false;
    log("ERROR: DHT does not work correctly.");
  } else {
    isTemperatureSensorWorking = true;
    isHumiditySensorWorking = true;
  }

  // initialize BMP180 module
  log("Communicating with BMP...");
  if (!bmp.begin()) {
    isPressureSensorWorking = false;
    log("ERROR: BMP180 does not work correctly.");
  } else {
    isPressureSensorWorking = true;
  }
  
  // initialize TSL module
  log("Communicating with TSL...");
  if (!tsl.begin()) {
    isLuminositySensorWorking = false;
    log("ERROR: TSL2561 does not work correctly.");
  } else {
    isLuminositySensorWorking = true;
    tsl.setGain(TSL2561_GAIN_16X);      // set 16x gain (for dim situations)
    tsl.setTiming(TSL2561_INTEGRATIONTIME_13MS);   // shortest integration time (bright light)
  }

  if (isnan(analogRead(SOILMOISTUREPIN))) {
    isSoilMoistureSensorWorking = false;
    log("Failed to read from soil moisture sensor");
  } else {
    isSoilMoistureSensorWorking = true;
  }

  /*
   * Publish current output status through the mailbox
   * "outputResponse" is the key used to inform about 
   * the status of the ouput
   */
  _outputsNumber = sizeof(outputPin) / sizeof(int);
  for(int i=0; i < _outputsNumber; i++) {
    pinMode(outputPin[i], OUTPUT);
    digitalWrite(outputPin[i], LOW);
  }
  String _response = "";
  for(int i=0; i < _outputsNumber; i++)
    _response += String(digitalRead(outputPin[i]));
  Bridge.put("outputResponse", _response);
  for(int i=0; i < _outputsNumber; i++) {
    log("Output Pin "+String(outputPin[i]) + " value " + String(digitalRead(outputPin[i])));
  }
  
  /*
   * Export the sensors' status outside
   * using the mailbox
   */
  Bridge.put(String("isRealTimeClockWorking"), String(isRealTimeClockWorking));
  Bridge.put(String("isTemperatureSensorWorking"), String(isTemperatureSensorWorking));
  Bridge.put(String("isHumiditySensorWorking"), String(isHumiditySensorWorking));
  Bridge.put(String("isPressureSensorWorking"), String(isPressureSensorWorking));
  Bridge.put(String("isSoilMoistureSensorWorking"), String(isSoilMoistureSensorWorking));
  Bridge.put(String("isLuminositySensorWorking"), String(isLuminositySensorWorking));
  
  /*
   * This is a reset of the variable just to be sure
   * we will not enter the main loop with no samples.
   * We are so close to the place where the cycle duration
   * is estimated that the difference between the _previousMicros
   * values and the currentMicros values is so small that 
   * we are pretty sure we will not exceed CYCLEDURATION
   */
  _previousMicros = micros();
  
  // Setup procedure complete
  digitalWrite(LEDPIN, LOW);
  Bridge.put(String("isSetupRunning"), String(false));
}

/*
 * Main loop
 */
void loop() {
  /*
   * Get the number of microseconds since the 
   * program started to execute the cycle every 
   * CYCLEDURATION microseconds
   */
  unsigned long currentMicros  = micros();
  
  /*
   * Export the values of the sensors and execute request
   * of changing outputs status (if any).
   */
  if (currentMicros - _previousMicros > CYCLEDURATION){  
    _previousMicros = currentMicros;
  
    // Inform the cycle has been started
    digitalWrite(LEDPIN, HIGH);

    // Read timestamp from hte real time clock
    DateTime now = rtc.now();
    String _timestamp = formattedDateTime(now);
  
    /* 
     * Always provide the status of the outputs
     */
    String _response = "";
    for(int i=0; i < _outputsNumber; i++)
      _response += "x";
    Bridge.put("outputResponse", _response);
    
    /*
     * Check if there is a request to change the
     * status of an output and in case change it.
     * "outputRequest" is the key used to receive 
     * the request to change the status of the outputs.
     * '0' means LOW, anything different from '0' means HIGH
     */
    char _output[_outputsNumber];
    String key = "outputRequest";
    if(Bridge.get(key.c_str(), _output, _outputsNumber) != 0) {
      for(int i=0; i < _outputsNumber; i++){
        if (_output[i]=='0')
          digitalWrite(outputPin[i], LOW);
        else
          digitalWrite(outputPin[i], HIGH);
      }
    }
    _response = "";
    for(int i=0; i < _outputsNumber; i++)
      _response += String(digitalRead(outputPin[i]));
    Bridge.put("outputResponse", _response);
    
    /*
     * Set the convertion factor to be used to
     * 1) calculate the average of the sensor's 
     *    values during the current cycle
     * 2) multiple by 100 to provide the sensors values
     *    as integer (2 digits after the decimal point)
     */
    float convertionFactor = 100.0/_samplesNumber;
    
    //Apply the convertion factor to each sensor
    unsigned long _ulHumidity = (unsigned long)(_humidity*convertionFactor);
    unsigned long _ulTemperature = (unsigned long)(_temperature*convertionFactor);
    unsigned long _ulPressure = (unsigned long)(_pressure*convertionFactor/100); //specific correction for pressure sensor
    unsigned long _ulSoilMoisture = (unsigned long)(_soilMoisture*convertionFactor);
    unsigned long _ulLuminosity = (unsigned long)(tsl.calculateLux(_full/_samplesNumber, _ir/_samplesNumber)*100);
      
    /*
     * The logging has been located in a IF statement
     * in order to enable/disable it at compilation time
     * to save the memory used by the sketch when debug 
     * will be no longer needed 
     * if(0) = logging disabled
     * if(1) = logging enabled
     */
    if(DEBUG) {
      log(" - - - - - - - - - - - - - - - - - - - - - - ");
      log("Timestamp: " + _timestamp); 
      log("Outputs status ([PinNumber] = 0/1):");
      String outputLog = " ->";
      for(int i=0; i < _outputsNumber; i++)
        outputLog += " [" + String(outputPin[i]) + "] = " + String(digitalRead(outputPin[i])) + " ";
      log(outputLog);
      log("Sensors values (based on " + String(_samplesNumber) + " samples):");
      if (isnan(_humidity) || isnan(_temperature)) {
        log("ERROR: Failed to read from DHT sensor");
      } else {
        log(" -> Temperature (DHT): " + String(_ulTemperature/100.0) + " *C");
        log(" -> Humidity    (DHT): " + String(_ulHumidity/100.0) + " %");  
      } 
      if (isnan(_pressure)) {
        log("Failed to read from BMP180 module");
      } else {
        /*
         * Here we have to divide by 10.000 because the pressure value from
         * the sensor is an unsigned long whos last 2 digits are considered
         * as the decimal part
         */
        log(" -> Pressure    (BMP): " + String((float)(_ulPressure)/100) + " Pa"); 
      }
      if (isnan(_soilMoisture)) {
        log("Failed to read from soil moisture sensor");
      } else {
        log(" -> Soil Moisture    : " + String((float)(_ulSoilMoisture)/100)); 
      }
      if (isnan(_ulLuminosity)) {
        log("Failed to read from TSL2561 module");
      } else {
        log(" -> Luminosity  (TSL): " + String(_ulLuminosity/100) + " Lux"); 
      }
    } 
    
    /*
     * Make sensors info available to outside
     * using the mailbox
     */
    Bridge.put(String("datetime"), _timestamp);
    Bridge.put(String("timestamp"), _timestamp);
    Bridge.put(String("temperature"), String(_ulTemperature));
    Bridge.put(String("humidity"), String(_ulHumidity));
    Bridge.put(String("pressure"), String(_ulPressure));
    Bridge.put(String("soilMoisture"), String(_ulSoilMoisture));
    Bridge.put(String("luminosity"), String(_ulLuminosity));
  
    // Reset the global variables for the next cycle
    _humidity = 0;
    _temperature = 0;
    _pressure = 0;
    _soilMoisture = 0;
    _ir = 0;
    _full = 0;
    _samplesNumber = 0;
  
    // Turn off the led to indicate the cycle has been completed
    digitalWrite(LEDPIN, LOW);
  } //end of IF statement to execute the cycle every CYCLEDURATION micros
  
  /*
   * If it is not time to export the values of the sensors
   * and change the outputs, keep collecting sensors samples
   */
  else   
  {
    // Read the humidity and the temperature from dht sensor
    _humidity += dht.readHumidity();
    _temperature += dht.readTemperature();
  
    // Read the pressure from bmp180 module
    _pressure += bmp.readPressure();
    
    // Read the soil moisture level
    _soilMoisture += analogRead(SOILMOISTUREPIN);
    
        // Read lux from the light sensor
    uint32_t luminosity = tsl.getFullLuminosity();
    if (!isnan(luminosity)) {
       uint16_t ir, full;
       _ir += luminosity >> 16;
       _full += luminosity & 0xFFFF;
    }
    
    //Increase number of samples
    _samplesNumber++;
   }
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
  Serial.begin(9600);
  while (!Serial);
}

void log(String message){
  Serial.println(message);
}
