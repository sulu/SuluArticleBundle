# Upgrade

## dev-develop

### Cachelifetime request attribute changed

The `_cacheLifetime` attribute available in the request parameter of a article
controller will return the seconds and don't need longer be resolved manually
with the cachelifetime resolver.

## 0.2.0

Reindex elastic search indexes:
* bin/adminconsole sulu:article:index-rebuild ###LOCALE### -live
* bin/adminconsole sulu:article:index-rebuild ###LOCALE###
