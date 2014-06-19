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
  Bridge.begin();
  Console.begin(); 
  while (!Console); // wait for Console port to connect.
  Console.println("You're connected to the Console");

  // initialize I2C
  Wire.begin();

  // initialize RealTimeClock module
  rtc.begin();
  if (! rtc.isrunning()) {
    Console.println("ERROR: RTC does not work correctly.");
    rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  } else {
    String unixFormattedDateTime = unixFormatDateTime(rtc.now());
    String message = "Set system date according to the onboard RTC (" + unixFormattedDateTime + ")";
    Console.println(message);
    Process p;            
    p.begin("date");      
    p.addParameter(unixFormattedDateTime); 
    p.run();
  }

  // initialize DHT sensor
  dht.begin();

  // initialize BMP180 module
  if (!bmp.begin()) {
	 Console.println("ERROR: BMP180 does not work correctly.");
  }

  // Setup procedure complete
  digitalWrite(LEDPIN, LOW);
}

void loop() {
  digitalWrite(LEDPIN, HIGH);

  // Read timestamp
  String _timestamp = formattedDateTime(rtc.now());
  Console.println("Timestamp from the RTC module: " + _timestamp); 
  
  // Read humidity and temperature from dht sensor
  float _humidity = dht.readHumidity();
  float _temperature = dht.readTemperature();
  if (isnan(h) || isnan(t)) {
    Console.println("Failed to read from DHT sensor");
  } else {
    Console.println("Data from DHT sensor: "); 
    Console.print("Humidity: "); 
    Console.print(_humidity);
    Console.println("%");
    Console.print("Temperature: "); 
    Console.print(_temperature);
    Console.println("*C");
  }
  
  // Read pressure and temperature from bmp180 module
  float _pressure = bmp.readPressure();
  _temperature = bmp.readTemperature();
  if (isnan(p) || isnan(t)) {
    Console.println("Failed to read from BMP180 module");
  } else {
    Console.println("Data from BMP180 module: "); 
    Console.print("Pressure: "); 
    Console.print(_pressure);
    Console.println("Pa");
    Console.print("Temperature: "); 
    Console.print(_temperature);
    Console.println("*C");
  }

  //Make sensors info available
  Bridge.put("temperature", String(_timestamp));
  Bridge.put("temperature", String(_temperature));

  digitalWrite(LEDPIN, LOW);
  //Wait one second
  delay(1000);

}

String unixFormattedDateTime(DateTime now){
  // Assumed syntax : [MMDDhhmm[[CC]YY][.ss]]
  String retval = "";
  retval += print2Char(now.month());
  retval += print2Char(now.day());
  retval += print2Char(now.hour());
  retval += print2Char(now.minute());
  if(now.year().lenght()==4) retval += now.year();
  else if(now.year().lenght()==2) retval += "20" + now.year();
  else retval += "2000";
  retval += ".";
  retval += print2Char(now.second());
  return retval;
}

String formattedDateTime(DateTime now){
  // Format: MM/DD/YYYY hh:mm:ss
  String retval = "";
  retval += print2Char(now.month()) + "/";
  retval += print2Char(now.day()) + "/";
  if(now.year().lenght()==4) retval += now.year();
  else if(now.year().lenght()==2) retval += "20" + now.year();
  else retval += "2000";
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
