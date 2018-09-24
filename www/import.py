#!/usr/bin/python
import os
import sys
import subprocess
from datetime import datetime
import filecmp
import shutil
import time

class bcolors:
    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'

debuglog = False
printlog = True

tlerstools = "/app/tilers-tools/tilers_tools/"

def dlog(msg):
    global debuglog
    if debuglog:
        print msg

def flog(msg):
    if not (os.path.isdir("logs")):
        os.mkdir("logs")
    with open(os.path.join(os.getcwd(),"logs/geotiff.import.log"), "a") as myfile:
        myfile.write(msg + "\r\n")
    print msg

def plog(msg):
    global printlog
    flog(msg)
    if printlog:
        print msg

def redisSetStatus(redis,redis_procid,redis_proc,ms):
    if(redis):
        redis_proc["status"] = ms
        redis.hmset("proc:" + str(redis_procid), redis_proc)

def redisGetCurProcId(redis):
    if not redis:
        return 0
    prcs = []
    keys = redis.keys("proc:*")
    for k in keys:
        p = redis.hgetall(k)
        if(p["active"] == "1"):
            prcs.append(k)

    return sorted(prcs)[0   ]


def main(argv):
    global makeit
    global debuglog

    flog("")
    flog(bcolors.HEADER + bcolors.BOLD + bcolors.UNDERLINE + "Cut GeoTIFF tiles" + bcolors.ENDC)
    redis = False
    redis_proc = {"mapid": "md5sum-mapname.tif", "name": "Map direct name", "status": "Last known status. (slicing, merging, done)", "active": "1"}
    redis_procid = 0

    tproc = 0
    tmerge = 0
    tnew = 0
    tskip = 0
    nowait = "-N" in argv

    tiff_dir = "/tiff/"
    tile_dir = "/tiles/"
    root_dir = os.getcwd()

    minargs = 2

    if "-z" in argv:
        minargs += 1

    if(len(argv) < minargs):
        flog( bcolors.FAIL + "Error: input file not specified" + bcolors.ENDC)
        flog( "Usage: python import.py tiff_dir tile_dir [options] [redis name folder]" + bcolors.ENDC)
        flog( "")
        flog( "Options:" + bcolors.ENDC)
        flog( "  -z        Zoom levels (-z 10,11,12,15)" + bcolors.ENDC)
        return

    tiff_dir = argv[0]
    tile_dir = argv[1]

    if "-d" in argv:
        debuglog = True

    if "redis" in argv:
        import redis
        redis = redis.StrictRedis(host='localhost', port=6379, db=0)
        redis_procid = redis.get("procid")
        if not redis_procid:
            redis_procid = 1000
        else:
            redis_procid = int(redis_procid) + 1
        redis_procid = str(redis_procid)
        redis.set("procid", redis_procid)
        redisidx = argv.index("redis")
        if len(argv) < redisidx+3:
            flog( bcolors.FAIL + "Error: redis missing mapid and name params" + bcolors.ENDC)
            return

        redis_proc["name"]  = argv[redisidx+1]
        redis_proc["mapid"] = argv[redisidx+2]
        redisSetStatus(redis,redis_procid,redis_proc,"Pending")

        if not nowait:
            if(redisGetCurProcId(redis) != "proc:"+redis_procid):
                flog( "Waiting for other process to complete  " + redis_procid + "    " +  redis.get("procid"))
            while (redisGetCurProcId(redis) != "proc:"+redis_procid):
                time.sleep(3)

    zooms = "10,11,12,13,14,15,16,17,18,19,20,21"

    if "-z" in argv:
        idx = argv.index("-z")
        if (len(argv) < idx+2):
            flog( bcolors.FAIL + "Error: zoom argument value invalid." + bcolors.ENDC)
            return
        zooms = argv[idx+1]
        # validate args
        for zoom in zooms.split(","):
            try:
                val = int(zoom)
            except ValueError:
                flog( bcolors.FAIL + "Error: zoom value \"" + zoom + "\" is invalid." + bcolors.ENDC)
                return
            if val > 24:
                flog( bcolors.FAIL + "Error: zoom value \"" + zoom + "\" is too large. Allowed range is 0..24" + bcolors.ENDC)
                return
            if val < 0:
                flog( bcolors.FAIL + "Error: zoom value \"" + zoom + "\" is too small. Allowed range is 0..24" + bcolors.ENDC)
                return
    else:
        flog("Zoom values not specified. Using default: \"" + zooms + bcolors.ENDC)

    # scan tifs
    tiff_list = []
    for file in os.listdir(tiff_dir):
        if file.endswith(".tif"):
            tiff_list.append([os.path.join(root_dir, tiff_dir, file),file])

    redisSetStatus(redis,redis_procid,redis_proc,"Started (0/"+str(len(tiff_list))+")")

    tiffnum = 1

    for tiff in tiff_list:
        redisSetStatus(redis,redis_procid,redis_proc,"Cutting tiles... ("+str(tiffnum)+"/"+str(len(tiff_list))+")")

        importdir = rreplace(tiff[0], ".tif", ".xyz", 1)
        importname = rreplace(tiff[1], ".tif", ".xyz", 1)

        flog( "Cutting tiles... " + tiff[0])
        tiledir_ok = os.path.isdir(tiff[0].replace(".tif",".xyz"))
        if tiledir_ok:
            tiledir_ok = False
            for idx, val in enumerate(os.walk(os.path.join(root_dir,tiff_dir,importname))):
                if tiledir_ok:
                    break
                if len(val[2]) > 0:
                    for png in val[2]:
                        if png.endswith(".png"):
                            tiledir_ok = True
                            break

        if not (tiledir_ok):
            os.system(tlerstools + 'tiler.py --cut --zoom=' + zooms + ' --release '+tiff[0]+' -p xyz')
            flog( "Tiles sliced")
        else:
            flog( "Tiles already sliced. Skipping... " + os.path.join(root_dir,tiff_dir,importname))

        flog( bcolors.HEADER + bcolors.BOLD + bcolors.UNDERLINE + "Import tiles" + bcolors.ENDC)
        redisSetStatus(redis,redis_procid,redis_proc,"Importing tiles... ("+str(tiffnum)+"/"+str(len(tiff_list))+")")

        # create zoom leval dirs
        for x in xrange(10,22):
            if not (os.path.isdir(os.path.join(root_dir, tile_dir, str(x)))):
                os.mkdir(os.path.join(root_dir, tile_dir, str(x)))

        full_tiff_dir = os.path.join(root_dir,tiff_dir)
        full_tile_dir = os.path.join(root_dir,tile_dir)

        for idx, val in enumerate(os.walk(os.path.join(root_dir,tiff_dir,importname))):
            if len(val[2]) > 0:
                for png in val[2]:
                    if ".json" in png:
                        continue
    
                    # png = x.png
                
                    tproc += 1
                    newpath = val[0] + "/" + png  # check file path  
                    newdir  = val[0]
    
                    dlog("importdir: " + importdir) # source tile dir (root, before zooms)
                    dlog("val[0]: " + val[0])       # source ../x/y/ dir full path
                    dlog("npath: " + newpath)       # source full file path

                    pngpath = newpath.replace(importdir, "", 1) # relative path of png  ex: 15/15567/5574.png

                    # newpath = /tiff/mapname.xyz/z/x/y.png
                    # val[0] = mapname.xyz/z/y
    
                    dstdirpath = val[0].replace(full_tiff_dir,full_tile_dir,1).replace(importname,"") # dirpath = /z/y
    
                    dlog("dirpath: " + dstdirpath)
    
                    dstpath = newpath.replace(importdir,full_tile_dir,1).replace(importname,"").replace("//","/")

                    if(os.path.isfile(dstpath)): 
                        plog(bcolors.WARNING + pngpath + " exists" + bcolors.ENDC)
                        plog("check files")
                        if filecmp.cmp(dstpath, newpath):
                            plog(dstpath + " and " + newpath + " seems equal... skip")
                            tskip += 1
                        else:
                            tmp_path = os.path.join(root_dir,"tmp","temp.png")
                            plog(" merge " + dstpath + " and " + newpath)
                            #flog("cmd: " + 'convert -composite -background none ' + dstpath + ' ' + newpath + ' ' + tmp_path)
                            os.system('convert -composite -background none ' + dstpath + ' ' + newpath + ' ' + tmp_path)
                            plog(" moving " + tmp_path)
                            move(tmp_path, dstpath)
                            tmerge += 1
                        plog("remove " + newpath)
                        os.remove(newpath)
                    else :
                        tnew += 1
                        plog(bcolors.OKGREEN + dstpath + " is new " + bcolors.ENDC)
                        if not os.path.isdir(dstdirpath):
                            plog(bcolors.OKBLUE + val[0] + " new dir" + bcolors.ENDC)
                            os.mkdir(dstdirpath)
                        dlog("Mover: " + newpath + " -> " + dstpath)
                        move(newpath,dstpath)
                        
        # dont do cleanup. shutil.rmtree(importdir)
        tiffnum += 1
    flog( "Processed " + str(tproc) + " tiles")
    if(redis):
        redis_proc["active"] = "0"
    redisSetStatus(redis,redis_procid,redis_proc,"Done. " + str(tnew) + " new, " + str(tmerge) + " merged, " + str(tskip) + " skipped")
    flog( " " + str(tnew) + " new")
    flog( " " + str(tmerge) + " merged")
    flog( " " + str(tskip) + " skipped")

def rreplace(s, old, new, occurrence):
    li = s.rsplit(old, occurrence)
    return new.join(li)

def move(src, dst):
    shutil.move(src, dst)
if __name__ == "__main__":
    try:
       main(sys.argv[1:])
    except:
        flog("Unexcepted error: " + str(sys.exc_info()[0]))
        flog(str(sys.exc_info()[1]))    

