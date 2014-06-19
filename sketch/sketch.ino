#include <Wire.h>
#include <Process.h>
#include <Console.h>
#include <DHT.h>
#include <RTClib.h>
#include <Adafruit_BMP085.h>
#include <WatchNWater.h>



DHT dht(DHTPIN, DHTTYPE);
RTC_DS1307 rtc;
Adafruit_BMP085 bmp;


void setup() {
  // Prepare the onboard led to blink once and then stay on 
  // until the setup procedure is complete
  pinMode(LEDPIN, OUTPUT);
  digitalWrite(LEDPIN, HIGH);
  
  // initialize serial communication:
  Serial.begin(9600);
  while (!Serial);
  Bridge.begin();
//  Console.begin(); 
//  while (!Console); // wait for Console port to connect.
//  Console.println("You're connected to the Console");
  Serial.println("You're connected");


  // initialize I2C
  Wire.begin();

  // initialize RealTimeClock module
  rtc.begin();
  if (! rtc.isrunning()) {
    Serial.println("ERROR: RTC does not work correctly.");
    rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  } else {
    setSystemDateTime(rtc.now());
  }

  // initialize DHT sensor
  dht.begin();

  // initialize BMP180 module
  if (!bmp.begin()) {
	 Serial.println("ERROR: BMP180 does not work correctly.");
  }

  // Setup procedure complete
  digitalWrite(LEDPIN, LOW);
  delay(2000);
}

void loop() {
  digitalWrite(LEDPIN, HIGH);

  DateTime now = rtc.now();
  //Check if a reset of system datetime is needed
  char _align_datetime[1];
  Bridge.get("align_datetime", _align_datetime, 1);
  int align_datetime = atoi(_align_datetime);
  if(align_datetime != 0){
    setSystemDateTime(now);
  }

  // Read timestamp
  String _timestamp = formattedDateTime(now);
  Serial.println("Timestamp from the RTC module: " + _timestamp); 
  
  // Read humidity and temperature from dht sensor
  float _humidity = dht.readHumidity();
  float _temperature = dht.readTemperature();
  if (isnan(_humidity) || isnan(_temperature)) {
    Serial.println("Failed to read from DHT sensor");
  } else {
    Serial.println("Data from DHT sensor: "); 
    Serial.print("Humidity: "); 
    Serial.print(_humidity);
    Serial.println("%");
    Serial.print("Temperature: "); 
    Serial.print(_temperature);
    Serial.println("*C");
  }
  
  // Read pressure and temperature from bmp180 module
  float _pressure = bmp.readPressure();
  _temperature = bmp.readTemperature();
  if (isnan(_pressure) || isnan(_temperature)) {
    Serial.println("Failed to read from BMP180 module");
  } else {
    Serial.println("Data from BMP180 module: "); 
    Serial.print("Pressure: "); 
    Serial.print(_pressure);
    Serial.println("Pa");
    Serial.print("Temperature: "); 
    Serial.print(_temperature);
    Serial.println("*C");
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

void setSystemDateTime(DateTime _now){
    String unixDateTime = unixFormattedDateTime(_now);
    String message = "Set system date according to the onboard RTC (" + unixDateTime + ")";
    Serial.println(message);
    Process p;            
    p.begin("date");      
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
