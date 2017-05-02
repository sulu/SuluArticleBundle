# Installation using ElasticSearch ^5.0

```bash
composer require ongr/elasticsearch-bundle:^5.0
```

## Configure the bundles:

```yml
# app/config/config.yml

ongr_elasticsearch:
    managers:
        default:
            index: 
                index_name: su_articles
            mappings:
                - SuluArticleBundle
        live:
            index:
                index_name: su_articles_live
            mappings:
                - SuluArticleBundle
```
