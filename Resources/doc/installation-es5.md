# Installation using ElasticSearch ^5.0

```bash
composer require handcraftedinthealps/elasticsearch-bundle:^5.0
```

## Configure the bundles:

```yml
# app/config/config.yml

ongr_elasticsearch:
    analysis:
        tokenizer:
            pathTokenizer:
                type: path_hierarchy
        analyzer:
            pathAnalyzer:
                tokenizer: pathTokenizer
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
