## Installation using ElasticSearch ^2.2

```bash
composer require ongr/elasticsearch-bundle:^5.0
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
    connections:
        default:
            index_name: su_articles_test
            analysis:
                tokenizer:
                    - pathTokenizer
                analyzer:
                    - pathAnalyzer
        live:
            index_name: su_articles_test_live
            analysis:
                tokenizer:
                    - pathTokenizer
                analyzer:
                    - pathAnalyzer
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
