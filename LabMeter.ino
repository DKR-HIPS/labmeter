
/********************** 

Lab-Meter Version: 2022-01-04 v1.0.0
Arduino for temperature and humidity measurement, display on LCD and send data to web server.
Parts: DHT22 sensor via 1-wire protocol, 16x2 LC-display via I2C, Arduino with Ethernet shield
(not using the SD card on the module and - different from Lcd-Meter project - not using a RTC module).
Also using the built-in watchdog functionality (8 seconds timeout).

**********************/

#include <Wire.h>
#include <LiquidCrystal_I2C.h> 
#include <DHT.h>
#include <SPI.h>
#include <Ethernet.h>
#include <avr/wdt.h>

#define LCDADDR 0x27      // I2C-address for LCD module (e.g. joy-it brand used in the example project) 
#define DHTPIN 2          // The sensor is connected to PIN 2 for 1-wire protocol    
#define DHTTYPE DHT22     // Specify the DHT22 sensor type

#define LIGHTBUTTON 7     // Pin 7 input for button to toggle LCD backlight on/off
#define SENDBUTTON 6      // Pin 6 input for button to trigger data sending manually

int devnum = 1;   // unique number for this device - important to change this if operating multiple devices

char night[] = "21";      // the hour when LCD backlight should automatically switch off (2 digits)
char morning[] = "07";     // the hour when LCD backlight is automatically switched on (2 digits)

char server[] = "mackrug.de";     // Name of webserver, where the measurement data will be sent (note: a working test server is configured here)
char serverpath[] = "/labmeter/sensor.php";  // path to PHP script on webserver, which is called via http. Starts with "/"
char security[] = "a4b3c2d1";     // 8 hex digits security code for this device, used in the HTTP GET request and checked by PHP script on server
char marker = char('|');          // recognition sign "|" used in the 21-characters date/time response string "|Y-m-d|H:i:s|" from the server

int sendspeed = 3 ;               // Interval (in minutes) for sending data to the Webserver (default: 3)
float tempcorr = 0.0 ;            // This correction value is applied to measured temperature (default: 0.0)

// MAC address from label on the ethernet shield or simply made up (but unique inside network)
byte mac[] = { 0xA0, 0xB1, 0xC2, 0xD3, 0xEF, 0xF7 };

// fallback IP address to use if DHCP was not successful - must match your local network settings
IPAddress ip(192, 168, 1, 92);

EthernetClient webclient;

LiquidCrystal_I2C lcd(LCDADDR, 16, 2);  // the 16x2 LCD module, called as "lcd"

DHT dht(DHTPIN, DHTTYPE);    // the sensor module called as "dht"

void setup() {
  
  pinMode(LIGHTBUTTON, INPUT_PULLUP);
  pinMode(SENDBUTTON, INPUT_PULLUP);
  
  Wire.begin();
  // Serial Monitor only for testing purposes if needed
  // Serial.begin(19200);
  
  lcd.init();
  // switch LCD backlight on (lcd.noBacklight(); switches it off).
  lcd.backlight(); 
  lcd.print("Lab-Meter   ["+String(devnum)+"]  ");
  lcd.setCursor(0,1);
  lcd.print("IP: trying DHCP ");

  dht.begin(); //DHT22 sensor start
  
  // Serial.println("Startup: trying DHCP.");
  if (Ethernet.begin(mac) == false)   // try to get IP-address via DHCP
    {
     // Serial.println("DHCP failed. Trying static.");
     lcd.setCursor(0,1);
     lcd.print("IP: using static");
     delay(1000);
     Ethernet.begin(mac, ip);     // if not successful, use the specified static fallback address
    }  
  delay(1000);
  
  // Serial.print("IP = ");
  // Serial.println(Ethernet.localIP());
  
  lcd.setCursor(0,1);
  lcd.print(Ethernet.localIP());
  lcd.print("         ");
  delay(1000);  

  wdt_enable(WDTO_8S);
}

float humidity;
float temperature;
String timecode ;
String datecode ;

bool displaylight = true;
bool lightswitch = true;
bool lbuttondown = false;
bool sbuttondown = false;
int timecounter = 1;
int sensorcounter = 1;
int displaycounter = 6;
int sendcounter = 1;

void loop() {

// interval to read new measurement from sensor, sensorcounter=500 means ca. every 10 seconds
sensorcounter--;
if (sensorcounter < 1)
  {
   sensorcounter = 500 ;
   lcd.setCursor(15,0);
   lcd.print( (char)127 );
   humidity = dht.readHumidity() ;         // read humidity
   delay(200);
   temperature = dht.readTemperature() + tempcorr ;   // read temperature and add the correction value
   lcd.setCursor(15,0);
   lcd.print(" ");
  }

if(digitalRead(LIGHTBUTTON) == LOW && lbuttondown == false)    // when lightbutton is pressed
  {
    lbuttondown = true;
    if(displaylight == true)
      {
       displaylight = false;
       lcd.noBacklight();
      }
    else
      {
       displaylight = true;
       lcd.backlight();
      }
  }
else if(digitalRead(LIGHTBUTTON) == HIGH && lbuttondown == true)
  { lbuttondown = false; }

if(digitalRead(SENDBUTTON) == LOW && sbuttondown == false)    // when sendbutton is pressed
  {
   sbuttondown = true;
   displaycounter = 1;
   sendcounter = 1;
  }
else if(digitalRead(SENDBUTTON) == HIGH && sbuttondown == true)
  { sbuttondown = false; }

timecounter--;
if (timecounter < 1)
{
  timecounter = 50;    // 50 x 20 ms = ca. 1 second interval
  wdt_reset();
    
  displaycounter--;
  if (displaycounter > 5) 
   {
    lcd.setCursor(0,0);
    lcd.print("Last  "+timecode+"  ");
    lcd.setCursor(0,1);
    lcd.print("sent: "+datecode);
   }
  else 
   {
    lcd.setCursor(0,0);
    lcd.print("Temp "+String(temperature)+" "+(char)223+"C   ");
    lcd.setCursor(0,1);
    lcd.print("Humidity "+String(humidity)+" %");
   }
    
  if (displaycounter < 1) 
   {
    // it will rotate between measurements and date/time display using ca. 10 seconds repeat time
    displaycounter = 10;
    Ethernet.maintain();

    // deactivate LCD backlight automatically at nighttime and activate in the morning
    if(lightswitch == true && timecode.substring(0,2) == night)
      {
       lightswitch = false;
       displaylight = false;
       lcd.noBacklight(); 
      }
    if(lightswitch == false && timecode.substring(0,2) == morning)
      {
       lightswitch = true;
       displaylight = true;
       lcd.backlight(); 
      }

    // interval for sending data to the webserver
    sendcounter--;
    if (sendcounter < 1)
     {
      sendcounter = 6 * sendspeed;
      wdt_reset();
      
      lcd.clear();
      lcd.print("Sending to:");
      lcd.setCursor(0,1);
      
      if (webclient.connect(server,80))
       {
        lcd.print(webclient.remoteIP());
        // Serial.println("Sending HTTP GET request to Server");
        // Send the HTTP GET request, with parameters: s=security code, d=device number, t=temperature, h=humidity
        webclient.println("GET "+String(serverpath)+"?s="+security+"&d="+String(devnum)+"&t="+String(temperature)+"&h="+String(humidity)+" HTTP/1.1");
        webclient.println("Host: "+String(server));
        webclient.println("Connection: close");
        webclient.println();
       } 
      else 
       {
        // if no connection to webserver possible
        lcd.print("failed");
        // Serial.println("Server connection failed");
       }

      wdt_reset();
      while (webclient.connected() && !webclient.available()) 
          { 
           delay(1);
          }

      wdt_reset();
      timecode = "";
      datecode = "";
      int dcount = 0;
      while ( (webclient.connected() || webclient.available()) && dcount < 20 ) 
          {
           char ch = webclient.read();
           if (ch == marker && dcount == 0)
           {
             dcount = 1;
           }
           else if (ch != marker && dcount > 0 && dcount < 9)
            {
              timecode += ch;
              dcount++;
            }  
           else if (ch != marker && dcount > 9 && dcount < 20)
            {
              datecode += ch;
              dcount++;
            } 
            else if (ch == marker)
            {
              dcount++; 
            }
          }
      if (dcount != 20)
      {
        timecode = "00:00:00";
        datecode = "0000-00-00";
      }
      
      // Serial.println("Sent: "+timecode+" "+datecode);
      lcd.setCursor(15,0);
      lcd.print( (char)126 );

      if (!webclient.connected()) 
         {
          webclient.stop();
         } 
         
      delay(200);
      lcd.setCursor(15,0);
      lcd.print(" ");
     }
  }  
 }

// the basic loop delay is 20 milliseconds
delay(20);
}
