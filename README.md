# TWCManager
TWCManager lets you control the amount of power delivered by a Generation 2 Tesla Wall Connector (TWC) to the car it's charging.  This can save around 6kWh per month when used to track a local green energy source like solar panels on your roof.  It can also avoid drawing grid energy for those without net metering or limit charging to times of day when prices are cheapest.

Due to hardware limitations, TWCManager will not work with Tesla's older High Power Wall Connectors (HPWCs) that were discontinued around April 2016.  TWCManager will also not work with Generation 3 TWCs released around Jan 15th 2020.  Sadly, gen 2 TWCs are no longer sold by Tesla and may become hard to acquire over time.  We don't expect to add support for gen 3 TWCs unless their wireless protocol is reverse engineered by a third party.

# Tracking solar edge
To track green energy with a solaredge inverter, it would be possible to go through the solaredge api but that would not allow for high polling rate as the api calls are limited.

Instead, it is possible to use a protocol called modbus. To activate modbus follow this [link](https://www.solaredge.com/sites/default/files/sunspec-implementation-technical-note.pdf)

The inverter needs to be connected through an ethernet cable (a firmware update disabled modbus over WiFi)

# Installation
For the RS485 connection, follow the pdf installation guide until Install TWCManager.

First step is to install some packages:

```
sudo apt-get install -y screen
sudo apt-get install -y git
sudo apt-get install python3-pip
git clone https://github.com/LennyBo/TWCManager.git
cd TWCManager
sudo pip3 install -r requirements.txt
sudo nano TWC/TWCManager.py
```

Here, change wiringMaxMapsAllTWCs and wiringMaxAmpsPerTWC to suit your installation.

Next step is to start the script on boot in case of power outages. I assume that you cloned inside /home/pi
If that is not the case, make sure to change the commands

Open /etc/rc.local and add this command before the exit 0
```
~/TWCManager/TWC/launch.sh
```
Now we also have to make that script execuable and reboot to test

```
chmod +x TWC/launch.sh
sudo reboot
```

