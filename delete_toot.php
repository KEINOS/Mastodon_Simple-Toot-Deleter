<?php
/**
 * How to use this script.
 * ---------------------------------------------------------------------
 *
 *   1. Run this script once as below and it'll create a JSON file.
 *       `$ php delete_toot.php`
 *   2. Then edit the JSON file and change the values below.
 *       - scheme        -> ex. https
 *       - host          -> ex. qiitadon.com
 *       - access_token  -> Get it from your instance's settings.
 *
 *       Optional:
 *           - id_skip    -> Set toot IDs to skip deleteing.
 *           - time_sleep -> Must be more than 1. The bigger the slower.
 *           - id_account -> Leave it blank then it'll auto fill.
 *   3. Run the script again.
 *
 *      Note1 : To run it background see below.
 *        1. Run as `$ nohup php ./delete_toot.php &`
 *        2. Then precc ^c (quit) the script.
 *        3. Run `$ tail -f nohup.out` to see the progress
 *      Note2 : Don't forget to delete 'nohup.out' file after finish.
 */

/* Constants (alphabetical order) ----------------------------------- */

define('PATH_DIR_CURRENT', realpath('./'));

const DIR_SEP          = DIRECTORY_SEPARATOR;
const NAME_FILE_OPTION = 'delete_toot.json';
const PATH_FILE_OPTION = PATH_DIR_CURRENT . DIR_SEP . NAME_FILE_OPTION;

//Minutes to cool down when too many request error from the server
const RELESE_TIME_TOO_MANY_REQUESTS = 5;

/* API Request Options ---------------------------------------------- */

$option_default = [
    'scheme'       => '',
    'host'         => '',
    'access_token' => '',
    'id_skip'      => [
        '',
        '',
    ],
    'time_sleep'   =>  1, // (^1)
    'id_account'   => '',
];

// (^1)
// Authorized API request must be less than 300 in 5min.
// ∴ Request limit 1 request/sec
// https://github.com/tootsuite/mastodon/blob/921b78190912b3cd74cea62fc3e773c56e8f609e/config/initializers/rack_attack.rb#L48-L50

/* Main ------------------------------------------------------------- */

$option = initialize_option($option_default);

// Create thread and call self recrucively.
while (0 < $option['statuses_left']) {
    // Fetch new toots
    echo_same_line('Fetching status and toots ...');
    $toots = fetch_100toots($option);

    // Create thread
    $size_chunk   = 20; //number of toots
    $thread_lists = apply_threads($toots, $size_chunk);

    // Run thread
    foreach ($thread_lists as $thread_list) {
        run_thread($thread_list, $option);
    }

    $statuses_left = fetch_statuses_count($option);
    $option['statuses_left'] = $statuses_left;
}

echo PHP_EOL;
echo 'All done.' . PHP_EOL;

die;

/* Functions (alphabetical order) ----------------------------------- */

function apply_account_id($option)
{
    $option['endpoint']    = '/api/v1/accounts/verify_credentials';
    $option['http_method'] = 'GET';

    $array_result = curl_api($option);

    if (! isset($array_result['id'])) {
        $msg_error = 'Can not fetch account ID.';
        die_error($array_result, $msg_error);
    }

    $option['id_account'] = $array_result['id'];

    unset($option['endpoint']);
    unset($option['http_method']);
    unset($option['host_ip']);

    // Overwrite option file
    return apply_option($option);
}

function apply_json($path_file_json, $data_array)
{
    $data_json = json_encode($data_array, JSON_PRETTY_PRINT);
    return file_put_contents($path_file_json, $data_json);
}

function apply_option($option_array)
{
    return apply_json(PATH_FILE_OPTION, $option_array);
}

function apply_threads($toots_array, $size_chunk)
{
    $toots_chunked = array_chunk($toots_array, $size_chunk);
    $thread_list   = array();

    foreach ($toots_chunked as $thread_id => $thread_toots) {
        $name_file_thread = "delete_toot_thread_${thread_id}.json";
        $path_file_thred  = PATH_DIR_CURRENT . DIR_SEP . $name_file_thread;
        if (apply_json($path_file_thred, $thread_toots)) {
            $thread_list[] = $path_file_thred;
        }
    }

    return $thread_list;
}

function check_option_requirement($option)
{
    $msg_error = '';

    if (empty($option['scheme'])) {
        $msg_error .= '- Scheme is required.';
        $msg_error .= PHP_EOL;
    }

    if (empty($option['host'])) {
        $msg_error .= '- Host name is required.';
        $msg_error .= PHP_EOL;
    }

    if (empty($option['access_token'])) {
        $msg_error .= '- Access_token is required.';
        $msg_error .= PHP_EOL;
    }

    if (! empty($msg_error)) {
        $msg_error  = PHP_EOL;
        $msg_error .= 'Please check the JSON file.' . PHP_EOL . $msg_error;
        die_error($msg_error, 'Empty setting on JSON file.');
    }

    $option['time_sleep'] = ($option['time_sleep'])?:1;
    $option['time_sleep'] = (integer) $option['time_sleep'];
    $option['id_skip']    = array_filter($option['id_skip']);

    return $option;
}

function curl_api(array &$option)
{
    $scheme       = $option['scheme'];
    $host_name    = $option['host'];
    $host_ip      = $option['host_ip'];
    $endpoint     = $option['endpoint'];
    $HTTP_method  = $option['http_method'];
    $access_token = $option['access_token'];

    sleep($option['time_sleep']);

    $url = "${scheme}://${host_name}${endpoint}";

    $header = [
        "Authorization: Bearer ${access_token}",
        'User-Agent: Qithub-Toot-Deleter',
    ];
    $context = [
        'http' => [
            'method' => $HTTP_method,
            'header' => implode("\r\n", $header),
        ],
    ];

    $query   = '';
    $query  .= " --header ''";
    $query  .= " -sS ${scheme}://${host_name}${endpoint};";

    $command = "curl -X ${HTTP_method}${query}";
    //$result_json = `$command 2>&1`;

    $steam_context = stream_context_create($context);
    $is_return_error = ( $result_json  = file_get_contents($url, false, $steam_context) ) ? false : true;

    $result_array = json_decode(trim($result_json), JSON_OBJECT_AS_ARRAY);
    $option['http_response_header'] = $http_response_header;

    if (! $is_return_error) {
        return $result_array;
    }

    if (ping($host_ip)) {
        echo PHP_EOL;
        echo "Error: Fail while requesting cURL." . PHP_EOL;
        if (! is_too_many_requests($option)) {
            print_r($result_json);
            print_r($http_response_header);
        }
    } else {
        $msg_error = 'Host is down ...';
        echo_same_line($msg_error);
        ping_until_up($host_ip);
    }

    return array();
}

function delete_toot(array &$option)
{
    $id_toot = $option['id_toot'];

    if (is_id_skip($option)) {
        echo PHP_EOL;
        echo "Toot in 'is_skip' found. Skipping toot ID ${id_toot}" . PHP_EOL;
        return null;
    }

    $option['endpoint']    = "/api/v1/statuses/${id_toot}";
    $option['http_method'] = 'DELETE';
    $result                = curl_api($option);

    return $result;
}

function delete_toots(string $path_file_toots, $option)
{
    $toots = fetch_json_as_array($path_file_toots);

    foreach ($toots as $toot) {
        $statuses_left     = $option['statuses_left'];
        $option['id_toot'] = $toot;

        $progress = format_progress($option);
        $progress = $progress . ' ' . $toot;
        $msg      = 'Deleting toot ... ' . $progress;
        $result   = delete_toot($option);

        echo_same_line($msg);

        if ($result == array() && ping_random($option['host_ip'])) {
            $msg = 'Deleted toot ... ' . $progress;
            --$option['statuses_left'];
            echo_same_line($msg);
        } else {
            if (isset($result['error'])) {
                $msg  = 'Error: ' . $result['error'];
                $msg .= " with toot ID: ${toot} ${progress}";
                echo_same_line($msg);
                sleep(1);
            } else {
                echo PHP_EOL;
                echo 'Error: Unknown responce' . PHP_EOL;
                print_r($result);
                ping_until_up($option['host_ip']);
            }
        }
    }

    return true;
}

function die_msg($status, $mix, $message = '')
{
    $msg  = PHP_EOL;
    $msg .= ('ok'===$status)  ? 'OK: ' : 'Error:';
    $msg .= (empty($message)) ? '' : $message . PHP_EOL;

    if (is_string($mix)) {
        $msg .= "\t" . escapeshellarg($mix);
    } else {
        $msg .= print_r($mix, true);
    }

    return $msg;
}

function die_error($mix, $message = '')
{
    $msg_error = die_msg('error', $mix, $message);
    file_put_contents('php://stderr', $msg_error . PHP_EOL);
    exit(1);
}

function die_ok($mix, $message = '')
{
    $msg_success = die_msg('ok', $mix, $message);
    echo PHP_EOL;
    echo $msg_success . PHP_EOL;
    exit(0);
}

function echo_eol($string)
{
    echo PHP_EOL, $string, PHP_EOL;
}

function echo_same_line($string)
{
    $string       = trim($string);
    $width_screen = get_screen_width();
    $line_blank   = str_repeat(' ', $width_screen);
    $line_string  = $string . $line_blank;
    $line_string  = substr($line_string, 0, $width_screen);

    echo "\r${line_string}";
}

function fetch_40toots(array &$option)
{
    $id_account = $option['id_account'];
    $param = '?limit=40';

    if (isset($option['id_min'])) {
        $param .= '&max_id=' . $option['id_min'];
    }

    $option['endpoint']    = "/api/v1/accounts/${id_account}/statuses${param}";
    $option['http_method'] = 'GET';

    return  curl_api($option);
}

function fetch_100toots(array &$option)
{
    $result = array();
    $count  = count($result);
    while ($count < 100) {
        $array = fetch_40toots($option);
        $option['id_min'] = fetch_id_next($option);

        $tmp = array();
        foreach ($array as $item) {
            if (isset($item['id'])) {
                $result[] = $item['id'];
                $tmp[] = $item['id'];
            }
        }
        $result = array_unique($result);
        $count  = count($result);
        echo_same_line("Fetching ${count} toots ...");
    }

    return $result;
}

function fetch_account_id(array $option)
{
    echo_same_line('Fetching account ID ...');

    if (empty($option['id_account'])) {
        $option = apply_account_id($option);
    }

    return $option['id_account'];
}

function fetch_id_next($option)
{
    if (! isset($option['http_response_header'])) {
        return '';
    }

    foreach ($option['http_response_header'] as $header) {
        if (strpos($header, 'Link:') !== false) {
            $url = trim(explode('>', $header)[0], 'Link: <');
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            return $query['max_id'];
        }
    }
}

function fetch_host_ip($option)
{
    if (! empty($option['host'])) {
        return gethostbyname($option['host']);
    }

    return false;
}

function fetch_json_as_array($path_file_json)
{
    if (! file_exists($path_file_json)) {
        $msg = 'File not found at:' . $path_file_json;
        echo_same_line($msg, 'JOSN file not found');

        return array();
    }

    $result_json  = file_get_contents($path_file_json);
    $result_array = json_decode($result_json, JSON_OBJECT_AS_ARRAY);
    $error_code   = json_last_error();

    if (JSON_ERROR_NONE !== $error_code) {
        $msg  = fetch_json_error_msg($error_code);
        $msg .= " at ${path_file_json}";
        die_error($msg, "While fetching JSON as array");
    }

    return $result_array;
}

function fetch_json_error_msg($error_code)
{
    $msg = '';

    switch ($error_code) {
        case JSON_ERROR_NONE:
            $msg = false;
            break;
        case JSON_ERROR_DEPTH:
            $msg = 'Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $msg = 'Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $msg = 'Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            $msg = 'Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            $msg = 'Unknown error';
            break;
    }

    return $msg;
}

function fetch_options(array $option_default)
{
    $result_array = fetch_json_as_array(PATH_FILE_OPTION);

    if ($result_array !== array()) {
        return $result_array;
    }

    $result_array = $option_default;

    if (apply_option($result_array)) {
        $msg  = 'File created. ';
        $msg .= 'Now edit the JSON file: ' . NAME_FILE_OPTION;
        die_error($msg, 'JSON file missing');
    } else {
        $msg = 'Please chmod of this directory.';
        die_error($msg, 'Fail writing file');
    }

    if (empty($result_array) && ! is_array($result_array)) {
        $msg = 'Something went wrong with file ' . NAME_FILE_OPTION . '.';
        die_error($msg, 'Invalid option file');
    }

    return $result_array;
}

function fetch_statuses_count(array $option)
{
    $option['endpoint']    = '/api/v1/accounts/verify_credentials';
    $option['http_method'] = 'GET';

    $array_result = curl_api($option);

    if (! is_too_many_requests($option)) {
        die_error($array_result, $msg_error);
    }

    if (! isset($array_result['statuses_count'])) {
        $msg_error = 'Can not fetch \'statues_count\'.';
    }

    return (integer) $array_result['statuses_count'];
}

function files_exists(array $path_file_lists)
{
    $result  = true;
    foreach ($path_file_lists as $path_file_list) {
        $bool   = file_exists($path_file_list);
        $result = $result && $bool;
    }

    return $result;
}

function format_progress($option)
{
    $statuses_left = $option['statuses_left'];
    $statuses_max  = $option['statuses_max'];
    $percentage    = ($statuses_left / $statuses_max)*100;
    $progress      = round($percentage, 2);
    $result        = "${statuses_left} / ${statuses_max}(${progress}%)";

    return $result;
}

function get_screen_width()
{
    $default_width = 70; //デフォルト幅

    if (! is_cli()) {
        return 'n/a';
    }

    if (! defined('SCREEN_WIDTH')) {
        $width = trim(`tput cols`); //バッククォートであることに注意
        $width = is_numeric($width) ? $width : $default_width;
        define('SCREEN_WIDTH', $width);
    }

    return SCREEN_WIDTH;
}

function initialize_option($option_default)
{
    // Fetch user options
    $option = fetch_options($option_default);
    $option = check_option_requirement($option);

    // Set additional options
    $option['host_ip']     = fetch_host_ip($option);
    $option['id_account']  = fetch_account_id($option);
    $option['endpoint']    = '';
    $option['http_method'] = '';

    // Number of due to delete toots
    $statuses_max = $statuses_left = fetch_statuses_count($option);
    $option['statuses_max']  = $statuses_max;
    $option['statuses_left'] = $statuses_left;

    return $option;
}

function is_cli()
{
    return PHP_SAPI === 'cli' || empty($_SERVER['REMOTE_ADDR']);
}

function is_id_skip($option)
{
    $target_id_toot = $option['id_toot'];
    $skip_id_list   = $option['id_skip'];
    $flipped        = array_flip($skip_id_list);

    return array_key_exists($target_id_toot, $flipped);
}

function is_too_many_requests($option)
{
    if (! isset($option['http_response_header'])) {
        return '';
    }

    foreach ($option['http_response_header'] as $header) {
        if ('HTTP/1.1 429 Too Many Requests' === trim($header)) {
            $count_down = RELESE_TIME_TOO_MANY_REQUESTS * 60;
            $msg_error = 'The server/host sais \'Too many requests\'.';
            while (0 < $count_down) {
                $msg = "${msg_error} Cooling down ... ${count_down}";
                echo_same_line($msg);
                sleep(1);
                --$count_down;
            }
            return  true;
        }
    }

    return (string) trim($header[0]);
}

function ping($host)
{
    $count   = 1;
    $timeout = 5;
    $cmd     = "ping -c ${count} -W ${timeout} %s";
    $result  = exec(sprintf($cmd, escapeshellarg($host)), $output, $return_value);

    return $return_value === 0;
}

function ping_until_up($host_ip)
{
    if (empty($host_ip)) {
        die_error('Please set host info at JSON file.', 'Empty host');
    }

    $is_host_down = true;

    while ($is_host_down) {
        echo_same_line("Pinging to {$host_ip} ...");
        sleep(1);

        if (ping($host_ip)) {
            echo_same_line('Host is UP!');
            $is_host_down = false;
        } else {
            echo_same_line('Host is down ...');
        }
        sleep(1);
    }

    return ! $is_host_down;
}

function ping_random($host)
{
    $percentage = 100;
    if (0 === rand(0, $percentage)) {
        return ping($host);
    } else {
        return true;
    }
}

function run_thread($path_file_toots, $option)
{
    if (! file_exists($path_file_toots)) {
        $msg = 'Can\'t find file at' . $path_file_toots;
        die_error($msg, 'File not found.');
    }

    $result = delete_toots($path_file_toots, $option);

    if (! $result) {
        $msg = 'Failed to delete toots on file: ' . $path_file_toots;
        die_error($msg, 'Delete');
    }

    if (! unlink($path_file_toots)) {
        $msg = 'Fail to unlink: ' . $path_file_toots;
        die_error($msg, 'Can NOT unlink');
    }

    return file_exists($path_file_toots);
}
