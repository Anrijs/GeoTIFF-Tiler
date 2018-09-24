import os
import sys
import redis

r = redis.StrictRedis(host='localhost', port=6379, db=0)
procid = int(r.get('procid'))
if not procid or procid < 1000:
	procid = 1000
else:
	procid += 1
procid = str(procid)


r.set('procid', procid)

proc = {"mapid": "md5sum-mapname.tif", "name": "Map direct name", "status": "Last known status. (slicing, merging, done)"}

r.hmset("proc:" + str(procid), proc)


#	print r.hgetall('p:1000')