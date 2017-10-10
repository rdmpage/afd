afd
===

Australian Faunal Directory data served from CouchDB


## Replication

Get a local CouchDB copy from remote Cloudant database:

```
curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '{ "target": "afd", "source": "https://rdmpage:<password>@rdmpage.cloudant.com/wikispecies-references"}'
```