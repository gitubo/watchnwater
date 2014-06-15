#include <WatchNWater.h>
//#include <DHT.h>
//#include <RTClib.h>

WnWSensor sensor;

void setup() {
  Serial.begin(57600);
  sensor.begin();
}

void loop() {
  // put your main code here, to run repeatedly:

}
