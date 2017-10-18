afd
===

Australian Faunal Directory data served from CouchDB

## Stats

Total number of publications parsed	25554
with identifiers	8771	(34%)
found in BioStor (BHL)	5672	(22%)
with DOIs	2751	(10%)
with Handles	137	(0%)
with one or more URLs	8004	(31%)
with PDFs	371	(1%)



## Replication

Get a local CouchDB copy from remote Cloudant database:

```
curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '{ "target": "afd", "source": "https://rdmpage:<password>@rdmpage.cloudant.com/afd"}'
```

Send local data to remote CouchDB

```
curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '{ "source": "afd", "target": "https://rdmpage:<password>@rdmpage.cloudant.com/afd"}'
```