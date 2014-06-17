#include <Wire.h>
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
  // Read timestamp
  DateTime now = rtc.now();
  Console.println("Timestamp from the RTC module: "); 
  Console.print(now.month(), DEC);
  Console.print('/');
  Console.print(now.day(), DEC);
  Console.print('/');
  Console.print(now.year(), DEC);
  Console.print(' ');
  Console.print(now.hour(), DEC);
  Console.print(':');
  Console.print(now.minute(), DEC);
  Console.print(':');
  Console.println(now.second(), DEC);
  
  // Read humidity and temperature from dht sensor
  float h = dht.readHumidity();
  float t = dht.readTemperature();
  if (isnan(h) || isnan(t)) {
    Console.println("Failed to read from DHT sensor");
  } else {
    Console.println("Data from DHT sensor: "); 
    Console.print("Humidity: "); 
    Console.print(h);
    Console.println("%");
    Console.print("Temperature: "); 
    Console.print(t);
    Console.println("*C");
  }
  
  // Read pressure and temperature from bmp180 module
  float p = bmp.readPressure();
  t = bmp.readTemperature();
  if (isnan(p) || isnan(t)) {
    Console.println("Failed to read from BMP180 module");
  } else {
    Console.println("Data from BMP180 module: "); 
    Console.print("Pressure: "); 
    Console.print(p);
    Console.println("Pa");
    Console.print("Temperature: "); 
    Console.print(t);
    Console.println("*C");
  }
  
  //Wait one second
  delay(1000);

}
