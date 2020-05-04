# Gramy.to (we-play.it)

Gramy.to (we-play.it) is web application intended for music band. It main destinity is display for all band members lyrics, chords, notes etc. to songs which are selected by one of them. Application working in modern web browser so you don't need to install any other applications.

## Getting Started

To install application on your webserver, in destination directory use command

```
git clone https://github.com/BartoszSiejka/gramy.to
```

or download zip file and unpack it.
In app directory use command

```
composer install
```

Add properly permissions for var/cache/, var/log/, public/uploads/ and backup.

Done! 

### Prerequisites

To properly work app you need LAMP (ex. Apache, PHP, MariaDB) with follow libraries:

* composer
* curl
* mod_rewrite
* openssl (optional)
* php (>=7.3)
* php7zip
* php-cgi
* php-cli
* php-common
* php-fpm
* php-gd
* php-mbstring
* php-mysql
* php-opcache
* php-pspell
* php-recode
* php-soap
* php-sqlite3
* php-tidy
* php-xmlrpc
* php-xsl
* php-zip
* php-apcu
* php-curl
* php-gettext
* php-imagick
* php-intl
* php-json
* php-ssh2
* php-xdebug
* firewall (optional)
* antivir (optional)
* dns server (np. dnsmasq, optional)

### Configuration

In .env file set parameter APP_ENV to prod or dev and add database authentication data:
    
    # do not use space before and after =
    DB_USER=YOUR_DB_USER
    DB_PASSWORD=YOUR_PASSWORD
    DB_NAME=YOUR_DB_NAME
    DB_HOST=DB_HOST
    DB_PORT=DB_PORT

Fill up data in parameters section in service.yaml

    parameters:
        # leave database fields
        database:
            user: '%env(DB_USER)%'
            password: '%env(DB_PASSWORD)%'
            name: '%env(DB_NAME)%'
        # in this section add SSH server authentication data to synchronized backup
        ssh:
            host: 
            port:
            user:
            password:
            remoteDir: # full path to directory on server
            anotherRemoteDir: # full path, only when you have another dir with backup from external app
        maxFilesInBackupDirectory: # max number of files in local and server backup directory
        usb:
            mountDirectory:  # mount point usb storage
            maxFilesToSend: # max number of files send to USB
            directoryName: # directory name with backup files

## Author

* **Bartosz Siejka** - [GitHub](https://github.com/BartoszSiejka) [https://bsiejka.com](https://bsiejka.com)

## License

This project is licensed under the MIT License (exclude plugins which may have other licenses) - see the [LICENSE.md](LICENSE.md) file for details
