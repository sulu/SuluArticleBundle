# Upgrade

## 0.5.0

### Elasticsearch 5.0

Now also support for ElasticSearch 5. To still be compatible with ^2.2, make sure you run: 
* composer require ongr/elasticsearch-bundle:1.2.9

## 0.4.0

### Cachelifetime request attribute changed

The `_cacheLifetime` attribute available in the request parameter of a article
controller will return the seconds and don't need longer be resolved manually
with the cachelifetime resolver.

## 0.2.0

Reindex elastic search indexes:
* bin/adminconsole sulu:article:index-rebuild ###LOCALE### -live
* bin/adminconsole sulu:article:index-rebuild ###LOCALE###

