# Simple toot deleter for Mastodon

[This simple PHP script](https://github.com/KEINOS/Mastodon_Simple-Toot-Deleter/blob/master/index.php) deletes all your toots from your Mastodon instance.

## How to use

1. Go to your Mastodon account settings and create an access_token.
1. Create a new PHP file.  (ex: `delete_toot.php`)
1. Copy and paste [the source](https://github.com/KEINOS/Mastodon_Simple-Toot-Deleter/blob/master/index.php).
1. Don't forget to `chmod` the script file as executable. (ex: `$ chmod 0755 delete_toot.php`)
1. Run the script once and it will create a JSON file for user settings.
    `$ php delete_toot.php`
1. Then edit the JSON file and change the values below.
    - scheme        -> ex. https
    - host          -> ex. qiitadon.com
    - access_token  -> Get it from your instance's settings.

    Optional:
    - id_skip    -> Set toot IDs to skip deleteing.
    - time_sleep -> Must be more than 1. The bigger the slower.
    - id_account -> Leave it blank then the script'll auto fill.
1. Run the script again and wait until it's done.

    Note1 : To **run it background** see below.
    1. Run as `$ nohup php ./delete_toot.php &`
    2. Then precc ^c (quit) the script.
    3. Run `$ tail -f nohup.put` to see the progress
    Note2 : Don't forget to delete 'nohup.out' file after finish.


## How do I get the access token?

1. Open your Mastodon account settings.
1. Go to "</> Development" setting and open "Your applications".
1. Create "NEW APPLICATION" with the settings below.

- Application name : Any name you want and easy to notice.
- Application website : Any web site, may be this GitHub page.
- Redirect URI : Leave it as is.
- Scopes : Check all.

Note: Don't forget to delete this application entry after use for sureness.

## Why it's slow?

This script deletes 1 toot/second because of the access limitation.

Since there's an access time limitation to use the API and if you request more than 300 authorized requests in 5 minutes ( 1 authorized request per second) you'll get a "Too many request" error. Therefore this script is slow.

- [See the current limit threshold](https://github.com/tootsuite/mastodon/blob/921b78190912b3cd74cea62fc3e773c56e8f609e/config/initializers/rack_attack.rb#L48-L50).

## Running the script in background

```
$ # Check the current PID to compare
$ ps
$ 
$ # Run the script in background then press Ctrl+c to exit
$ nohup php ./delete_toot.php &
$
$ # Recheck the PID to see if the script is running
$ ps
$
$ # See the progress to check
$ tail -f nohup.out
```

## Operation environment tested

|Topic|Content|
|:---|:---|
|Confirmation date|2018/03/12|
|Mastodon|v2.1.0|
|OS|macOS High Sierra（OSX 10.13.3）|
|Machine| MacBookPro（Retina, 13-inch, Early 2015）|
|`$ php -v`|PHP 7.1.8 (cli) (built: Aug  7 2017 15:02:45) ( NTS )<br>Copyright (c) 1997-2017 The PHP Group<br>Zend Engine v3.1.0, Copyright (c) 1998-2017 Zend Technologies|
|`$ bash --version`|GNU bash, version 3.2.57(1)-release (x86_64-apple-darwin17)<br>Copyright (C) 2007 Free Software Foundation, Inc.|
|`$ curl --version`|curl 7.54.0 (x86_64-apple-darwin17.0) libcurl/7.54.0 LibreSSL/2.0.20 zlib/1.2.11 nghttp2/1.24.0<br>Protocols: dict file ftp ftps gopher http https imap imaps ldap ldaps pop3 pop3s rtsp smb smbs smtp smtps telnet tftp<br>Features: AsynchDNS IPv6 Largefile GSS-API Kerberos SPNEGO NTLM NTLM_WB SSL libz HTTP2 UnixSockets HTTPS-proxy |

|Topic|Content|
|:---|:---|
|Confirmation date|2018/04/14|
|Mastodon|v2.2.0|
|OS|Raspbian GNU/Linux 8.0 (jessie)|
|Machine| RaspberryPi 3<br>Hardware	: BCM2835<br>Revision	: a22082|
|`$ php -v`|PHP 7.1.12-1+0~20171129100725.11+jessie~1.gbp8ded15 (cli) (built: Dec  1 2017 04:22:35) ( NTS )<br>Copyright (c) 1997-2017 The PHP Group<br>Zend Engine v3.1.0, Copyright (c) 1998-2017 Zend Technologies<br>with Zend OPcache v7.1.12-1+0~20171129100725.11+jessie~1.gbp8ded15, Copyright (c) 1999-2017, by Zend Technologies|
|`$ bash --version`|GNU bash, バージョン 4.3.30(1)-release (arm-unknown-linux-gnueabihf)<br>Copyright (C) 2013 Free Software Foundation, Inc.|
|`$ curl --version`|curl 7.38.0 (arm-unknown-linux-gnueabihf) libcurl/7.38.0 OpenSSL/1.0.1t zlib/1.2.8 libidn/1.29 libssh2/1.4.3 librtmp/2.3<br>Protocols: dict file ftp ftps gopher http https imap imaps ldap ldaps pop3 pop3s rtmp rtsp scp sftp smtp smtps telnet tftp <br>Features: AsynchDNS IDN IPv6 Largefile GSS-API SPNEGO NTLM NTLM_WB SSL libz TLS-SRP|

