#!/bin/bash
set -ev

echo $ES_VERSION;

if [[ "${ES_VERSION}" = "2.4.4" ]]; then
        wget https://download.elastic.co/elasticsearch/release/org/elasticsearch/distribution/tar/elasticsearch/${ES_VERSION}/elasticsearch-${ES_VERSION}.tar.gz

else
        wget https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-${ES_VERSION}.tar.gz
fi

tar -xzf elasticsearch-${ES_VERSION}.tar.gz
./elasticsearch-${ES_VERSION}/bin/elasticsearch > elasticsearch.log 2>&1 &
wget -q --waitretry=1 --retry-connrefused -T 10 -O - http://127.0.0.1:9200