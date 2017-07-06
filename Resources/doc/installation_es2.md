## Installation using ElasticSearch ^2.2

```bash
composer require ongr/elasticsearch-bundle:^1.2
```

## Configure the bundles:

```yml
# app/config/config.yml

ongr_elasticsearch:
    connections:
        default:
            index_name: su_articles_test
        live:
            index_name: su_articles_test_live
    managers:
        default:
            connection: default
            mappings:
                - SuluArticleBundle
        live:
            connection: live
            mappings:
                - SuluArticleBundle
```
