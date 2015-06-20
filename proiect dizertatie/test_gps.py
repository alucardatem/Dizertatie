#!/usr/bin/python
import gps
# Listen on port 2947 (gpsd) of localhost
session = gps.gps("localhost", "2947") 
session.stream(gps.WATCH_ENABLE | gps.WATCH_NEWSTYLE) 
while True:
	try:
		
		report = session.next()
		if report['class'] == 'TPV':
			if hasattr(report, 'time'):
				if hasattr(report,'lat'):
			#report.lat = report.lat+0.000086333
			#report.lon = report.lon+0.000383667
					print report.lat,',',report.lon
				else:
					print 0,',',0
				session = None
				quit()

	except KeyError:
		pass
	except KeyboardInterrupt:
		quit()
	except StopIteration:
		session = None
		print "GPSD has terminated"
