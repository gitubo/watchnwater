#include <Wire.h>
#include <Process.h>
#include <Console.h>
#include <DHT.h>
#include <RTClib.h>
#include <Adafruit_BMP085.h>

#define DHTPIN 8    
#define DHTTYPE DHT22 
#define LEDPIN 13

#define OUTPUT_NUMBER 4
#define OUTPUT1 1
#define OUTPUT2 2
#define OUTPUT3 3
#define OUTPUT4 4
#define OUTPUT1_PIN 4
#define OUTPUT2_PIN 5
#define OUTPUT3_PIN 6
#define OUTPUT4_PIN 7

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
    rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  } else {
    setSystemDateTime(rtc.now());
  }

  // initialize DHT sensor
  log("Communicating with DHT...");
  dht.begin();

  // initialize BMP180 module
  log("Communicating with BMP...");
  if (!bmp.begin()) {
	 log("ERROR: BMP180 does not work correctly.");
  }

  // Setup procedure complete
  digitalWrite(LEDPIN, LOW);
}

void loop() {
  digitalWrite(LEDPIN, HIGH);

  DateTime now = rtc.now();

  //Check if a pin status has to be changed to LOW
  char _output[2];
  int output;
  for(int i=0; i < OUTPUT_NUMBER; i++){
    String key = "OUTPUT" + i;
    Bridge.get(key.c_str(), _output, 2);
    output = atoi(_output);
    output = changeOutput(i, output);
    Bridge.put(key, String(output));
  }

  //Check if a reset of system datetime is needed
  char _align_datetime[1];
  Bridge.get("align_datetime", _align_datetime, 1);
  int align_datetime = atoi(_align_datetime);
  if(align_datetime != 0){
    setSystemDateTime(now);
    Bridge.put(String("align_datetime"), String("0"));
  }

  // Read timestamp
  String _timestamp = formattedDateTime(now);
  log("Timestamp from the RTC module: " + _timestamp); 
  
  // Read humidity and temperature from dht sensor
  float _humidity = dht.readHumidity();
  float _temperature = dht.readTemperature();
  if (isnan(_humidity) || isnan(_temperature)) {
    log("ERROR: Failed to read from DHT sensor");
  } else {
    log("Data from DHT sensor: "); 
    log(" -> Humidity: " + String(_humidity) + "%"); 
    log(" -> Temperature: " + String(_temperature) + "*C"); 
  }
  
  // Read pressure and temperature from bmp180 module
  float _pressure = bmp.readPressure();
  _temperature = bmp.readTemperature();
  if (isnan(_pressure) || isnan(_temperature)) {
    log("Failed to read from BMP180 module");
  } else {
    log("Data from BMP180 module: "); 
    log(" -> Pressure: " + String(_pressure/100.0) + "Pa"); 
    log(" -> Temperature: " + String(_temperature) + "*C");
  }
  
  //Make sensors info available
  Bridge.put(String("timestamp"), _timestamp);
  Bridge.put(String("temperature"), String(_temperature));
  Bridge.put(String("humidity"), String(_humidity));
  Bridge.put(String("pressure"), String(_pressure));
  Bridge.put(String("soil_moisture"), String(""));
  Bridge.put(String("luminosity"), String(""));

  digitalWrite(LEDPIN, LOW);
  
  //Wait one second
  delay(1000);
}

int changeOutput(int _output, int _value){
  switch(_output){
    case OUTPUT1:
      if(_value == LOW)
        digitalWrite(OUTPUT1_PIN, LOW);
      else
        digitalWrite(OUTPUT1_PIN, HIGH);
      return digitalRead(OUTPUT1_PIN);
    case OUTPUT2:
      if(_value == LOW)
        digitalWrite(OUTPUT2_PIN, LOW);
      else
        digitalWrite(OUTPUT2_PIN, HIGH);
      return digitalRead(OUTPUT2_PIN);
    case OUTPUT3:
      if(_value == LOW)
        digitalWrite(OUTPUT3_PIN, LOW);
      else
        digitalWrite(OUTPUT3_PIN, HIGH);
      return digitalRead(OUTPUT3_PIN);
    case OUTPUT4:
      if(_value == LOW)
        digitalWrite(OUTPUT4_PIN, LOW);
      else
        digitalWrite(OUTPUT4_PIN, HIGH);
      return digitalRead(OUTPUT4_PIN);
    default:
      return 2;
  }
}

void setSystemDateTime(DateTime _now){
    String unixDateTime = unixFormattedDateTime(_now);
    log("Set system date according to the onboard RTC (" + String(unixDateTime) + ")");
    Process p;            
    p.begin("echo arduino | sudo date");      
    p.addParameter(unixDateTime); 
    p.run();

}

String unixFormattedDateTime(DateTime now){
  // Assumed syntax : [MMDDhhmm[[CC]YY][.ss]]
  String retval = "";
  retval += print2Char(now.month());
  retval += print2Char(now.day());
  retval += print2Char(now.hour());
  retval += print2Char(now.minute());
  if(now.year()>99) retval += now.year();
  else retval += "20" + now.year();
  retval += ".";
  retval += print2Char(now.second());
  return retval;
}

String formattedDateTime(DateTime now){
  // Format: MM/DD/YYYY hh:mm:ss
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

String print2Char(int value){
  if(value<10) return "0"+String(value);
  return String(value);  
}

void logBegin(){
  if(1) {
    Serial.begin(9600);
    while (!Serial);
  }  
}

void log(String message){
  if(1) Serial.println(message);
}
