<?php
//base http://docs.aws.amazon.com/general/latest/gr/sigv4-signed-request-examples.html
//AWS署名v4テスト用

function hash_sha256_raw($msg, $key)
{
    return hash_hmac("sha256", $msg, $key, true);
}
 
function getSignature($key, $dateStamp, $regionName, $serviceName)
{
    $kDate    = hash_sha256_raw($dateStamp, "AWS4$key");
    $kRegion  = hash_sha256_raw($regionName, $kDate);
    $kService = hash_sha256_raw($serviceName, $kRegion);
    $kSigning = hash_sha256_raw("aws4_request", $kService);
    return $kSigning;
}
function main($access_key,$secret_key,$canonical_uri){
	//パラメータ
	$service               = 's3';
	$region                = 'ap-northeast-1';
//	$canonical_uri         = 'URL指定' ;
//	$access_key            = 'アクセスキー';
//	$secret_key            = '秘密鍵';

	$method                = 'GET';
	$host                  = "${service}-${region}.amazonaws.com";
	 
	$canonical_querystring = '';
	$signed_headers        = 'host;x-amz-content-sha256;x-amz-date';
	 
	$endpoint              = "http://${host}${canonical_uri}";
	$payload               = '';
	 
	 
	 
	 
	$algorithm = 'AWS4-HMAC-SHA256';
	 
	 
	//現在時刻の取得
	$t         = time();
	$amzdate   = gmdate('Ymd\THis\Z', $t);
	$datestamp = gmdate('Ymd', $t);
	 
	//payload作成
	$payload_hash = hash('sha256', $payload);
	 
	//ヘッダ作成
	$canonical_headers  = "host:$host\n";
	$canonical_headers .= "x-amz-content-sha256:$payload_hash\n";
	$canonical_headers .= "x-amz-date:$amzdate\n";
	 
	//リクエスト生成
	$canonical_request  = "$method\n";
	$canonical_request .= "$canonical_uri\n";
	$canonical_request .= "$canonical_querystring\n";
	$canonical_request .= "$canonical_headers\n";
	$canonical_request .= "$signed_headers\n";
	$canonical_request .= "$payload_hash";
	 
	 
	$credential_scope = "$datestamp/$region/$service/aws4_request";
	$string_to_sign   = "$algorithm\n";
	$string_to_sign  .= "$amzdate\n";
	$string_to_sign  .= "$credential_scope\n";
	$string_to_sign  .= hash('sha256', $canonical_request);
	 
	$signing_key = getSignature($secret_key, $datestamp, $region, $service);
	$signature   = hash_hmac('sha256', $string_to_sign, $signing_key);
	 
	 
	$authorization_header = "$algorithm Credential=$access_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";
	$headers = array(
		"x-amz-content-sha256: $payload_hash",
		"Authorization: $authorization_header",
		"x-amz-date: $amzdate",
		);
	$request_url = rtrim("$endpoint?$canonical_querystring","?");
	 
	$context = array(
		"http" => array(
			"method"  => $method,
			"header"  => implode("\r\n", $headers)
		)
	);

	$ret = file_get_contents($request_url, false, stream_context_create($context));

	prn("payload_hash",$payload_hash);
	prn("canonical_headers",$canonical_headers);
	prn("string_to_sign",$string_to_sign);
	prn("canonical_request",$canonical_request);
	prn("Request",print_r($context,1));
	prn("Response-header",print_r($http_response_header,1));
	prn("Response-body",$ret);
}
function prn($k,$v){
	echo str_repeat(">",40)."\n";
	echo ">>>>>$k\n";
	echo $v;
	echo "\n";
	echo str_repeat("<",40)."\n";
}
main($argv[1],$argv[2],$argv[3]);
