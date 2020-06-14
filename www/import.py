#!/usr/bin/python
import os
import sys
import subprocess
from datetime import datetime
import filecmp
import shutil
import time
import traceback
import threading

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

redis = False
redis_proc = {"mapid": "md5sum-mapname.tif", "name": "Map direct name", "status": "Last known status. (slicing, merging, done)", "active": "1"}
redis_procid = 0

tlerstools = "/app/tilers-tools/tilers_tools/"

def dlog(msg):
    global debuglog
    if debuglog:
        print msg

def flog(msg):
    try:
        if not (os.path.isdir("logs")):
            os.mkdir("logs")
        if os.path.isdir("logs"):
            with open(os.path.join(os.getcwd(),"logs/geotiff.import.log"), "a") as myfile:
                myfile.write(msg + "\r\n")
    except Exception as ex:
        traceback.print_exc()
    print msg

def plog(msg):
    global printlog
    flog(msg)


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
    global redis
    global redis_procid
    global redis_proc

    flog("")
    flog(bcolors.HEADER + bcolors.BOLD + bcolors.UNDERLINE + "Cut GeoTIFF tiles" + bcolors.ENDC)
    redis = False
    keepTiles = False
    doImportTiles = True

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

    if "-list" in argv:
        minargs += 1

    if(len(argv) < minargs):
        flog( bcolors.FAIL + "Error: input file not specified" + bcolors.ENDC)
        flog( "Usage: python import.py tiff_dir tile_dir [options] [redis name folder]" + bcolors.ENDC)
        flog( "")
        flog( "Options:" + bcolors.ENDC)
        flog( "  -z <levels>        Zoom levels (-z 10,11,12,15)" + bcolors.ENDC)
        flog( "  -keep              Don't remove tiles in tiff directory" + bcolors.ENDC)
        flog( "  -noimport          Don't import tiles to map layer." + bcolors.ENDC)
        flog( "  -threads <count>   Run in multiple threads." + bcolors.ENDC)
        flog( "  -list <file?       Import files from list." + bcolors.ENDC)
        flog( "                     tile_dir still must be specified, but it can be non-existing." + bcolors.ENDC)
        return

    tiff_dir = argv[0]
    tile_dir = argv[1]

    if "-keep" in argv:
        keepTiles = True

    if "-noimport" in argv:
        doImportTiles = False

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

    flist = []
    if "-list" in argv:
        idx = argv.index("-list")
        if (len(argv) < idx+2):
            flog( bcolors.FAIL + "Error: list argument value invalid." + bcolors.ENDC)
            return
        fname = argv[idx+1]
        f = open(fname, "r")
        for x in f:
            ln = x.strip()
            if len(ln) < 1 or ln[0] == "#":
                continue # skip comments and empty lines
            flist.append(ln)
        if len(flist) == 0:
            flog(bcolors.ERROR + "Warning: list file is empty. Nothing to do." + bcolors.ENDC)
            return         

    zooms = "8,9,10,11,12,13,14,15,16,17,18,19,20,21"

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

    threadCount = 1
    if "-threads" in argv:
        idx = argv.index("-threads")
        if (len(argv) < idx+2):
            flog( bcolors.FAIL + "Error: threads argument value invalid." + bcolors.ENDC)
            return
        threadCount = int(argv[idx+1])

        if (threadCount < 1):
            flog("Invalid thread count. Using default: \"" + str(threadCount) + bcolors.ENDC)


    ext = ".tif"
    if "-mapext" in argv:
        ext = ".map"

    # scan tifs
    tiff_list = []
    for file in os.listdir(tiff_dir):
        if file.endswith(ext):
            tiff_list.append([os.path.join(root_dir, tiff_dir, file),file])

    if len(flist) > 0:
        temp = []
        for f in flist:
            found = None
            for tif in tiff_list:
                if tif[1] == f:
                    found = tif
                    temp.append(tif)
                    break
            if found == None:
                flog( bcolors.WARNING + "Warning: File \"" + f + "\" not found. Will skip..." + bcolors.ENDC)
        tiff_list = temp
    redisSetStatus(redis,redis_procid,redis_proc,"Started (0/"+str(len(tiff_list))+")")

    threads = []

    for tiff in tiff_list:
        while threading.active_count() > threadCount:
            # wait
            time.sleep(1)

        th = CutThread(tiff, ext, root_dir, tile_dir, tiff_dir, zooms)
        threads.append(th)
        th.start()

    for t in threads:
        t.join()
        flog( "Thread joined.")

    for tiff in tiff_list:
        if doImportTiles:
            res = importTiles(tiff, ext, root_dir, tile_dir, tiff_dir, keepTiles)
            tproc += res[0]
            tnew += res[1]
            tmerge += res[2]
            tskip += res[3]
        else:
            flog( "Will not import.")

    flog( "Thread Done.")

    if doImportTiles:
        flog( "Processed " + str(tproc) + " tiles")

    if(redis):
        redis_proc["active"] = "0"

    if doImportTiles:
        redisSetStatus(redis,redis_procid,redis_proc,"Done. " + str(tnew) + " new, " + str(tmerge) + " merged, " + str(tskip) + " skipped")
        flog( " " + str(tnew) + " new")
        flog( " " + str(tmerge) + " merged")
        flog( " " + str(tskip) + " skipped")
    else:
        redisSetStatus(redis,redis_procid,redis_proc,"Done. Tiles sliced.")

class CutThread (threading.Thread):
    def __init__(self, tiff, ext, root_dir, tile_dir, tiff_dir, zooms):
        threading.Thread.__init__(self)
        self.tiff = tiff
        self.ext = ext
        self.root_dir = root_dir
        self.tile_dir = tile_dir
        self.tiff_dir = tiff_dir
        self.zooms = zooms

    def run(self):
        print "Starting "
        self.cutTiles(self.tiff, self.ext, self.root_dir, self.tile_dir, self.tiff_dir, self.zooms)
        print "Exiting "

    def cutTiles(self, tiff, ext, root_dir, tile_dir, tiff_dir, zooms):
        global redis
        global redis_procid
        global redis_proc

        redisSetStatus(redis,redis_procid,redis_proc,"Cutting tiles... ("+str(tiff[1])+")")

        importdir = rreplace(tiff[0], ext, ".xyz", 1)
        importname = rreplace(tiff[1], ext, ".xyz", 1)

        flog( bcolors.HEADER + bcolors.BOLD + bcolors.UNDERLINE + "Cutting tiles... ("+tiff[1]+")" + bcolors.ENDC)
        flog( "Cutting tiles... " + tiff[0])
        sliced_tile_dir = tiff[0].replace(ext,".xyz")
        tiledir_ok = os.path.isdir(sliced_tile_dir)
        if tiledir_ok:
            tiledir_ok = False
            z_diff = ""
            for z in zooms.split(","):
                if not (os.path.isdir(os.path.join(sliced_tile_dir, z))):
                    z_diff += z + ","
            if (len(z_diff) > 0):
                tiledir_ok = False
                zooms = z_diff[:-1]
            else:
                for idx, val in enumerate(os.walk(os.path.join(root_dir,tiff_dir,importname))):
                    if tiledir_ok:
                        break
                    if len(val[2]) > 0:
                        for png in val[2]:
                            if png.endswith(".png"):
                                tiledir_ok = True
                                break

        if not (tiledir_ok):
            os.system(tlerstools + 'tiler.py --zoom=' + zooms + ' --release "'+tiff[0]+'" -p xyz')
            flog( "Tiles sliced")
        else:
            flog( "Tiles already sliced. Skipping... " + os.path.join(root_dir,tiff_dir,importname))


def importTiles(tiff, ext, root_dir, tile_dir, tiff_dir, keepTiles):
    global redis
    global redis_procid
    global redis_proc

    tproc = 0
    tnew = 0
    tmerge = 0
    tskip = 0

    importdir = rreplace(tiff[0], ext, ".xyz", 1)
    importname = rreplace(tiff[1], ext, ".xyz", 1)

    flog( bcolors.HEADER + bcolors.BOLD + bcolors.UNDERLINE + "Import tiles" + bcolors.ENDC)
    redisSetStatus(redis,redis_procid,redis_proc,"Importing tiles... ("+tiff[1]+")")

    # create zoom leval dirs
    for x in xrange(0,24):
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

                dstdirpath = val[0].replace(full_tiff_dir,full_tile_dir,1).replace(importname,"").replace("//","/") # dirpath = /z/y

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
                        os.system('convert -composite -background none "' + dstpath + '" "' + newpath + '" "' + tmp_path + '"')
                        plog(" moving " + tmp_path)
                        move(tmp_path, dstpath, False) # This one is temp file. Only move.
                        tmerge += 1
                    plog("remove " + newpath)
                    if not keepTiles:
                        os.remove(newpath)
                else :
                    tnew += 1
                    plog(bcolors.OKGREEN + dstpath + " is new " + bcolors.ENDC)
                    if not os.path.isdir(dstdirpath):
                        plog(bcolors.OKBLUE + val[0] + " new dir" + bcolors.ENDC)
                        os.mkdir(dstdirpath)
                    dlog("Mover: " + newpath + " -> " + dstpath)
                    move(newpath,dstpath, keepTiles)

    # dont do cleanup. shutil.rmtree(importdir)
    return [tproc,tnew,tmerge,tskip]

def rreplace(s, old, new, occurrence):
    li = s.rsplit(old, occurrence)
    return new.join(li)

def move(src, dst, copy=False):
    if (copy):
	shutil.copy(src, dst)
    else:
        shutil.move(src, dst)

if __name__ == "__main__":
    try:
        main(sys.argv[1:])
    except Exception as ex:
        for e in sys.exc_info():
            print str(e)
            print "<br>"

        # cancel redis
        if redis:
            redis_proc["active"] = "0"
            redisSetStatus(redis,redis_procid,redis_proc,"Failed. " + str(sys.exc_info()[1]))
            flog("Redis stooped")
        flog("Unexcepted error: " + str(sys.exc_info()[0]))
        flog(str(sys.exc_info()[1]))    

