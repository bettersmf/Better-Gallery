<?php

if (!defined('SMF'))
	die('No direct access.');

/**
 * Better Storage shared backend helpers.
 * Phase 1: local/S3 configuration and signed S3 connectivity checks.
 * Designed for reuse by Better Gallery and Better Downloads.
 */

function betterstorage_s3_normalize_prefix($prefix)
{
	$prefix = trim(str_replace('\\', '/', (string) $prefix), '/ ');
	return $prefix === '' ? '' : $prefix . '/';
}

function betterstorage_s3_host($bucket, $region, $endpoint = '')
{
	$bucket = trim((string) $bucket);
	$region = trim((string) $region) ?: 'us-east-1';
	$endpoint = trim((string) $endpoint);

	if ($endpoint !== '')
	{
		$endpoint = preg_replace('~^https?://~i', '', rtrim($endpoint, '/'));
		return $bucket !== '' ? $bucket . '.' . $endpoint : $endpoint;
	}

	return $bucket . '.s3.' . $region . '.amazonaws.com';
}

function betterstorage_s3_signing_key($secret, $date, $region, $service = 's3')
{
	$k_date = hash_hmac('sha256', $date, 'AWS4' . $secret, true);
	$k_region = hash_hmac('sha256', $region, $k_date, true);
	$k_service = hash_hmac('sha256', $service, $k_region, true);
	return hash_hmac('sha256', 'aws4_request', $k_service, true);
}

function betterstorage_s3_request($method, $bucket, $region, $access_key, $secret_key, $path = '/', $query = '', $endpoint = '', $body = '', $extra_headers = array())
{
	$method = strtoupper((string) $method);
	$region = trim((string) $region) ?: 'us-east-1';
	$bucket = trim((string) $bucket);
	$access_key = trim((string) $access_key);
	$secret_key = trim((string) $secret_key);

	$missing = array();
	if ($bucket === '')
		$missing[] = 'bucket name';
	if ($access_key === '')
		$missing[] = 'access key ID';
	if ($secret_key === '')
		$missing[] = 'secret access key';

	if (!empty($missing))
		return array('ok' => false, 'status' => 0, 'error' => 'Missing S3 setting(s): ' . implode(', ', $missing) . '.', 'body' => '');

	$host = betterstorage_s3_host($bucket, $region, $endpoint);

	if (!function_exists('curl_init'))
		return array('ok' => false, 'status' => 0, 'error' => 'PHP cURL is required for Amazon S3.', 'body' => '');

	$path = '/' . ltrim((string) $path, '/');
	$encoded_path = implode('/', array_map('rawurlencode', explode('/', $path)));
	if ($encoded_path === '')
		$encoded_path = '/';
	elseif ($encoded_path[0] !== '/')
		$encoded_path = '/' . $encoded_path;
	$encoded_path = str_replace('%2F', '/', $encoded_path);

	$amz_date = gmdate('Ymd\\THis\\Z');
	$date_stamp = gmdate('Ymd');
	$payload_hash = hash('sha256', (string) $body);

	$headers = array(
		'host' => $host,
		'x-amz-content-sha256' => $payload_hash,
		'x-amz-date' => $amz_date,
	);
	foreach ($extra_headers as $name => $value)
		$headers[strtolower(trim((string) $name))] = trim((string) $value);
	ksort($headers);

	$canonical_headers = '';
	foreach ($headers as $name => $value)
		$canonical_headers .= $name . ':' . preg_replace('/\\s+/', ' ', $value) . "\n";
	$signed_headers = implode(';', array_keys($headers));

	$canonical_request = $method . "\n" . $encoded_path . "\n" . (string) $query . "\n" . $canonical_headers . "\n" . $signed_headers . "\n" . $payload_hash;
	$scope = $date_stamp . '/' . $region . '/s3/aws4_request';
	$string_to_sign = "AWS4-HMAC-SHA256\n" . $amz_date . "\n" . $scope . "\n" . hash('sha256', $canonical_request);
	$signature = hash_hmac('sha256', $string_to_sign, betterstorage_s3_signing_key($secret_key, $date_stamp, $region));
	$authorization = 'AWS4-HMAC-SHA256 Credential=' . trim((string) $access_key) . '/' . $scope . ', SignedHeaders=' . $signed_headers . ', Signature=' . $signature;

	$url = 'https://' . $host . $encoded_path . ($query !== '' ? '?' . $query : '');
	$curl_headers = array('Authorization: ' . $authorization);
	foreach ($headers as $name => $value)
		$curl_headers[] = $name . ': ' . $value;

	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_HTTPHEADER => $curl_headers,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => true,
		CURLOPT_CONNECTTIMEOUT => 8,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_FOLLOWLOCATION => false,
	));
	if ($method !== 'GET' && $method !== 'HEAD' && $body !== '')
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	if ($method === 'HEAD')
		curl_setopt($ch, CURLOPT_NOBODY, true);

	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	$header_size = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	curl_close($ch);

	$response_body = is_string($response) ? substr($response, $header_size) : '';
	$ok = $curl_error === '' && $status >= 200 && $status < 300;

	$error = '';
	if (!$ok)
	{
		if ($curl_error !== '')
			$error = $curl_error;
		elseif (preg_match('~<Code>([^<]+)</Code>~', $response_body, $m))
			$error = 'AWS ' . $m[1] . ' (HTTP ' . $status . ')';
		else
			$error = 'Amazon S3 returned HTTP ' . $status . '.';
	}

	return array('ok' => $ok, 'status' => $status, 'error' => $error, 'body' => $response_body);
}

function betterstorage_s3_test($config)
{
	$config = is_array($config) ? $config : array();
	$result = betterstorage_s3_request(
		'HEAD',
		$config['bucket'] ?? '',
		$config['region'] ?? 'us-east-1',
		$config['access_key'] ?? '',
		$config['secret_key'] ?? '',
		'/',
		'',
		$config['endpoint'] ?? ''
	);

	if (!empty($result['ok']))
		$result['message'] = 'Connection successful. Better Storage can reach and authenticate with this S3 bucket.';
	else
		$result['message'] = $result['error'];

	return $result;
}


/** Build the complete object key used inside an S3 bucket. */
function betterstorage_s3_object_key($prefix, $relative)
{
    $prefix = betterstorage_s3_normalize_prefix($prefix);
    $relative = ltrim(str_replace('\\', '/', (string) $relative), '/');
    return $prefix . $relative;
}

/** Upload a local file to S3 without loading the entire file into PHP memory. */
function betterstorage_s3_put_file($config, $relative_key, $local_path, $content_type = 'application/octet-stream')
{
    $config = is_array($config) ? $config : array();
    if (!is_file($local_path) || !is_readable($local_path))
        return array('ok'=>false, 'status'=>0, 'error'=>'The local file could not be read for S3 upload.');

    $bucket = trim((string) ($config['bucket'] ?? ''));
    $region = trim((string) ($config['region'] ?? 'us-east-1')) ?: 'us-east-1';
    $access = trim((string) ($config['access_key'] ?? ''));
    $secret = trim((string) ($config['secret_key'] ?? ''));
    $endpoint = trim((string) ($config['endpoint'] ?? ''));
    if ($bucket === '' || $access === '' || $secret === '')
        return array('ok'=>false, 'status'=>0, 'error'=>'S3 is not fully configured.');
    if (!function_exists('curl_init'))
        return array('ok'=>false, 'status'=>0, 'error'=>'PHP cURL is required for Amazon S3.');

    $object_key = betterstorage_s3_object_key($config['prefix'] ?? '', $relative_key);
    $path = '/' . ltrim($object_key, '/');
    $encoded_path = implode('/', array_map('rawurlencode', explode('/', $path)));
    $encoded_path = str_replace('%2F', '/', $encoded_path);
    if ($encoded_path === '' || $encoded_path[0] !== '/') $encoded_path = '/' . $encoded_path;
    $host = betterstorage_s3_host($bucket, $region, $endpoint);
    $amz_date = gmdate('Ymd\\THis\\Z');
    $date_stamp = gmdate('Ymd');
    $payload_hash = hash_file('sha256', $local_path);
    $headers = array(
        'content-type' => (string) $content_type,
        'host' => $host,
        'x-amz-content-sha256' => $payload_hash,
        'x-amz-date' => $amz_date,
    );
    ksort($headers);
    $canonical_headers='';
    foreach ($headers as $name=>$value) $canonical_headers .= $name . ':' . preg_replace('/\\s+/', ' ', trim($value)) . "\n";
    $signed_headers=implode(';', array_keys($headers));
    $canonical_request="PUT\n".$encoded_path."\n\n".$canonical_headers."\n".$signed_headers."\n".$payload_hash;
    $scope=$date_stamp.'/'.$region.'/s3/aws4_request';
    $string_to_sign="AWS4-HMAC-SHA256\n".$amz_date."\n".$scope."\n".hash('sha256',$canonical_request);
    $signature=hash_hmac('sha256',$string_to_sign,betterstorage_s3_signing_key($secret,$date_stamp,$region));
    $authorization='AWS4-HMAC-SHA256 Credential='.$access.'/'.$scope.', SignedHeaders='.$signed_headers.', Signature='.$signature;
    $url='https://'.$host.$encoded_path;
    $curl_headers=array('Authorization: '.$authorization);
    foreach($headers as $name=>$value) $curl_headers[]=$name.': '.$value;
    $fp=fopen($local_path,'rb');
    if (!$fp) return array('ok'=>false,'status'=>0,'error'=>'The local file could not be opened for S3 upload.');
    $ch=curl_init($url);
    curl_setopt_array($ch,array(
        CURLOPT_UPLOAD=>true,
        CURLOPT_INFILE=>$fp,
        CURLOPT_INFILESIZE=>(int) filesize($local_path),
        CURLOPT_HTTPHEADER=>$curl_headers,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HEADER=>true,
        CURLOPT_CONNECTTIMEOUT=>10,
        CURLOPT_TIMEOUT=>0,
        CURLOPT_FOLLOWLOCATION=>false,
    ));
    $response=curl_exec($ch);
    $error=curl_error($ch);
    $status=(int) curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
    curl_close($ch); fclose($fp);
    $ok=$error==='' && $status>=200 && $status<300;
    return array('ok'=>$ok,'status'=>$status,'error'=>$ok?'':($error!==''?$error:'Amazon S3 returned HTTP '.$status.'.'),'key'=>$object_key,'response'=>$response);
}

function betterstorage_s3_delete($config, $relative_key)
{
    $key = betterstorage_s3_object_key($config['prefix'] ?? '', $relative_key);
    return betterstorage_s3_request('DELETE', $config['bucket'] ?? '', $config['region'] ?? 'us-east-1', $config['access_key'] ?? '', $config['secret_key'] ?? '', '/' . $key, '', $config['endpoint'] ?? '');
}

/** Create a short-lived signed GET URL. */
function betterstorage_s3_presign_get($config, $relative_key, $expires = 300, $download_name = '')
{
    $bucket=trim((string)($config['bucket']??'')); $region=trim((string)($config['region']??'us-east-1'))?:'us-east-1';
    $access=trim((string)($config['access_key']??'')); $secret=trim((string)($config['secret_key']??'')); $endpoint=trim((string)($config['endpoint']??''));
    if($bucket===''||$access===''||$secret==='') return '';
    $expires=max(60,min(3600,(int)$expires));
    $object_key=betterstorage_s3_object_key($config['prefix']??'', $relative_key);
    $path='/' . ltrim($object_key,'/');
    $encoded_path=implode('/',array_map('rawurlencode',explode('/',$path))); $encoded_path=str_replace('%2F','/',$encoded_path);
    if($encoded_path===''||$encoded_path[0]!=='/') $encoded_path='/'.$encoded_path;
    $host=betterstorage_s3_host($bucket,$region,$endpoint);
    $amz_date=gmdate('Ymd\\THis\\Z'); $date_stamp=gmdate('Ymd'); $scope=$date_stamp.'/'.$region.'/s3/aws4_request';
    $query=array(
        'X-Amz-Algorithm'=>'AWS4-HMAC-SHA256',
        'X-Amz-Credential'=>$access.'/'.$scope,
        'X-Amz-Date'=>$amz_date,
        'X-Amz-Expires'=>(string)$expires,
        'X-Amz-SignedHeaders'=>'host',
    );
    if($download_name!=='') $query['response-content-disposition']='attachment; filename="'.str_replace(array('"','\\'), '_', basename($download_name)).'"';
    ksort($query);
    $parts=array(); foreach($query as $k=>$v) $parts[]=rawurlencode($k).'='.rawurlencode($v); $canonical_query=implode('&',$parts);
    $canonical_headers='host:'.$host."\n";
    $canonical_request="GET\n".$encoded_path."\n".$canonical_query."\n".$canonical_headers."\nhost\nUNSIGNED-PAYLOAD";
    $string_to_sign="AWS4-HMAC-SHA256\n".$amz_date."\n".$scope."\n".hash('sha256',$canonical_request);
    $signature=hash_hmac('sha256',$string_to_sign,betterstorage_s3_signing_key($secret,$date_stamp,$region));
    return 'https://'.$host.$encoded_path.'?'.$canonical_query.'&X-Amz-Signature='.$signature;
}

function betterstorage_s3_download_to_file($config, $relative_key, $dest)
{
    $url=betterstorage_s3_presign_get($config,$relative_key,300);
    if($url==='') return false;
    $fp=@fopen($dest,'wb'); if(!$fp) return false;
    $ch=curl_init($url); curl_setopt_array($ch,array(CURLOPT_FILE=>$fp,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>0));
    $ok=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch); fclose($fp);
    if(!$ok||$status<200||$status>=300||!is_file($dest)){@unlink($dest); return false;} return true;
}
