<?php

require 'config.php';

function OpenAICurl($endpoint, $method, $postfields = []){
    global $config;

    $api_token = $config['openai']['api_key'];

    $url = "https://api.openai.com/v1/$endpoint";

	$headers = array(
		'Content-Type:application/json',
        'Authorization:Bearer '.$api_token,
    );

    $ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if(in_array($method, array("POST", "PATCH", "PUT"))){
		if($method == "POST") curl_setopt($ch, CURLOPT_POST, TRUE);
		else curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return($result);
}

function createWordPressPostWithChatGPT($params){
    $endpoint = "chat/completions";
    $method = "POST";
    if($params['type'] == "post") $prompt = "Write an SEO-optimized WordPress article in ".$params['language']." focusing on the keyword '".$params['keyword']."'. Include an engaging title, a meta description, and the article content in HTML format.";
    else $prompt = "Write an SEO-optimized WordPress page in ".$params['language']." about '".$params['topic']."'. Include an engaging title, a meta description, and the article content in HTML format.";
    $postfields = [
        "model" => "gpt-4o-2024-08-06",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are an expert WordPress content writer."
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "max_tokens" => 1500,
        "response_format" => [
            "type" => "json_schema",
            "json_schema" => [
                "name" => "wordpress_article",
                "strict" => true,
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "title" => [
                            "type" => "string"
                        ],
                        "meta_description" => [
                            "type" => "string"
                        ],
                        "article_content" => [
                            "type" => "string",
                            "description" => "The main content of the article in HTML format"
                        ]
                    ],
                    "required" => ["title", "meta_description", "article_content"],
                    "additionalProperties" => false
                ]
            ]
        ]
    ];
    $result = json_decode(OpenAICurl($endpoint, $method, $postfields));
    return $result;
}

$params = [
    'type' => "page",
    'topic' => "What is Pipedrive and how it can help your business grow",
    'language' => "Spanish (from Chile)"
];

$result = createWordPressPostWithChatGPT($params);

echo "<pre>Title: "; var_dump(json_decode($result->choices[0]->message->content)); echo "</pre>";

?>