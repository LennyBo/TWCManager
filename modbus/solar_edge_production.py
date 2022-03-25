#!/usr/bin/python3

if __name__ == "__main__":
    import solaredge_modbus as smdb
    from settings import solar_edge_ip, solar_edge_port
    import argparse
    
    parser = argparse.ArgumentParser(description=f"Uses modbus TCP to interogate the solar edge inverter on ip {solar_edge_ip}:{solar_edge_port}")
    parser.add_argument('-grid',action="store_true",  help='returns the grid power')
    parser.add_argument('-solar',action="store_true",  help='returns the solar power')
    parser.add_argument('-house',action="store_true",  help='returns the house\'s consumption')
    
    args = parser.parse_args()
    if(not args.solar and not args.grid and not args.house):
        print("Missing one argument -grid or -solar or -house\nUse -h for help")
        exit(-1)
    
    inv = smdb.Inverter(host=solar_edge_ip,port=solar_edge_port)
    
    if(inv.connect()):
        meter = inv.meters()["Meter1"]
        
        solarPower = inv.read("power_ac")["power_ac"]
        gridPower = meter.read("power")["power"]
        housePower = solarPower - gridPower
        
        inv.disconnect()
        meter.disconnect()
        
        #print(f"Solar: {solarPower / 1000} kWh\tGrid: {gridPower / 1000} kWh\tHouse: {housePower / 1000} kWh")
        
        if(args.solar):
            print(solarPower)
        elif(args.grid):
            print(gridPower)
        elif(args.house):
            print(housePower)
        exit(0)
    else:
        print("Inverter unreachable")
        inv.disconnect()
        exit(-1)