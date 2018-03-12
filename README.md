# Simple toot deleter for Mastodon

[This simple PHP script](https://github.com/KEINOS/Mastodon_Simple-Toot-Deleter/blob/master/index.php) deletes all your toots from your Mastodon instance.

## How to use

1. Go to your Mastodon account settings and create an access_token.
1. Create a new PHP file.
1. Copy [the source](https://github.com/KEINOS/Mastodon_Simple-Toot-Deleter/blob/master/index.php).
1. Edit the user settings such as the "host" name and the "access_token".
1. Don't forget to `chmod` the script file as executable. (ex: `$ chmod 0755 yourfile.php`)
1. Run the script and wait until it's done.


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
$ # Run the script in background
$ nohup php ./yourfile.php &
$ # Press Ctrl+c to exit
$ # Recheck the PID to see if the script is running
$ ps
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


