# Installation using ElasticSearch ^2.2

```bash
composer require handcraftedinthealps/elasticsearch-bundle:^1.2
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
