#!/bin/bash
set -ev

if [[ "${ES_VERSION}" = "2.4.4" ]]; then
        wget https://download.elastic.co/elasticsearch/release/org/elasticsearch/distribution/tar/elasticsearch/${ES_VERSION}/elasticsearch-${ES_VERSION}.tar.gz
        tar -xzf elasticsearch-${ES_VERSION}.tar.gz
        ./elasticsearch-${ES_VERSION}/bin/elasticsearch > elasticsearch.log 2>&1 &
        wget -q --waitretry=1 --retry-connrefused -T 10 -O - http://127.0.0.1:9200
else
        curl -O https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-${ES_VERSION}.deb
        sudo dpkg -i --force-confnew elasticsearch-${ES_VERSION}.deb
        sudo service elasticsearch start
fi