<?php

/* User settings ---------------------------------------------------- */

$schema       = 'https';
$host         = 'qiitadon.com';
$access_token = '';

/* API Request Options ---------------------------------------------- */

$option = [
    'schema'       => $schema,
    'host'         => $host,
    'access_token' => $access_token,
    'endpoint'     => '',
    'http_method'  => '',
    'id_account'   => '',
    'time_sleep'   => 1, //sec (^1)
];

// (^1)
// Authorized API request must be less than 300 in 5min.
// ∴ Request limit 1 request/sec
// https://github.com/tootsuite/mastodon/blob/921b78190912b3cd74cea62fc3e773c56e8f609e/config/initializers/rack_attack.rb#L48-L50

/* Main ------------------------------------------------------------- */

$statuses_max         = $statuses_left = fetch_statuses_count($option);
$option['id_account'] = fetch_account_id($option);

while (0 < $statuses_left) {
    $statuses_left = fetch_statuses_count($option);
    $toots         = fetch_20toots($option);

    foreach ($toots as $toot) {
        $progress = round(($statuses_left / $statuses_max)*100, 2);

        echo "Deleting toot ... ";
        echo "${statuses_left}/${statuses_max} (${progress}% Left).";
        echo "\r";

        $option['id_toot'] = $toot['id'];
        delete_toot($option);
        $statuses_left--;
    }
}

echo PHP_EOL;
echo 'All done.' . PHP_EOL;

die;

/* Functions (alphabetical order) ----------------------------------- */

function curl_api(array $option)
{
    $schema       = $option['schema'];
    $host         = $option['host'];
    $access_token = $option['access_token'];
    $endpoint     = $option['endpoint'];
    $HTTP_method  = $option['http_method'];

    sleep($option['time_sleep']);

    $query   = '';
    $query  .= " --header 'Authorization: Bearer ${access_token}'";
    $query  .= " -sS ${schema}://${host}${endpoint};";

    $command = "curl -X ${HTTP_method}${query}";

    if ($result_json = `$command`) {
        $result_array = json_decode($result_json, JSON_OBJECT_AS_ARRAY);
        return $result_array;
    } else {
        $msg_error = 'Failed while requesting API.';
        echo_error($command, $msg_error);
    }
}

function echo_error($mix, $message = '')
{
    $msg_error  = PHP_EOL . "Error: ";
    $msg_error .= (empty($message)) ? '' : $message . PHP_EOL;

    if (is_string($mix)) {
        $msg_error .= "\t" . $mix;
    } else {
        $msg_error .= print_r($mix, true);
    }

    file_put_contents('php://stderr', $msg_error . PHP_EOL);

    exit(1);
}

function fetch_20toots(array $option)
{
    $id_account            = $option['id_account'];

    $option['endpoint']    = "/api/v1/accounts/${id_account}/statuses";
    $option['http_method'] = 'GET';

    return  curl_api($option);
}


function fetch_account_id(array $option)
{
    if (! empty($option['id_account'])) {
        return $option['id_account'];
    }

    $option['endpoint']    = '/api/v1/accounts/verify_credentials';
    $option['http_method'] = 'GET';

    $result_array = curl_api($option);

    if (! isset($result_array['id'])) {
        $msg_error = 'Can not fetch account ID.';
        echo_error($result_array, $msg_error);
    }

    return  $result_array['id'];
}

function fetch_statuses_count(array $option)
{
    $option['endpoint']    = '/api/v1/accounts/verify_credentials';
    $option['http_method'] = 'GET';

    $result_array = curl_api($option);

    if (! isset($result_array['statuses_count'])) {
        $msg_error = 'Can not fetch \'statues_count\'.';
        echo_error($result_array, $msg_error);
    }

    return (integer) $result_array['statuses_count'];
}

function delete_toot(array $option)
{
    $id_toot               = $option['id_toot'];
    $option['endpoint']    = "/api/v1/statuses/${id_toot}";
    $option['http_method'] = 'DELETE';

    return ($result = curl_api($option));
}
