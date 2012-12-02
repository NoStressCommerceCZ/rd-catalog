#!/bin/sh

curl -XGET 'http://localhost:9200/test_index/test_type/_search?pretty=true' -d '{
	
	"query":{
	 	"filtered":{
		 	"query" : {
                "query_string" : {
                    "query" : "Lorem"
                }
            },
			"filter": {
				"and":[
					{
						"or":[{
							"term":{"size":"L"}
						}]
					},{
						"or":[{
							"term":{"yesno":"yes"}
						}]
					}
				]
			}
		}
	},
	"facets":{
		"_facet_size":{
			"terms":{	
				"field":"size",
				"order":"reverse_count",
				"all_terms":true				
			},                 
			"facet_filter": {
				"and":[
					{
						"query" : {
		                "query_string" : {
		                    "query" : "Lorem"
		                }
		            }
					},
					{
						"or":[{
							"term": {"yesno": "yes"}
						}]
					}
				]
			},
			"global": true
		},
		"_facet_yesno":{
			"terms":{	
				"field":"yesno",
				"order":"reverse_count",
				"all_terms":true				
			},                 
			"facet_filter": {
				"and":[
					{
						"query" : {
		                "query_string" : {
		                    "query" : "Lorem"
		                }
		            }
					},
					{
						"or":[{
							"term": {"size": "L"}
						}]
					}
				]
			},
			"global": true
		}
	}
}'