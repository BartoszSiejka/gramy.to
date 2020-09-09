# Gramy.to

ENGLISH [HERE](README_EN.md)

ZOBACZ DEMO [TUTAJ](http://gramy.to.bsiejka.com/)

Gramy.to jest aplikacją webową przeznaczoną dla zespołów muzycznych. Jej głównym przeznaczeniem jest wyświetlanie wszystkim członkom zespołu tekstów, akordów, nut itp. do utworów wybieranych przez jedną osobę z zespołu. Aplikacja obsługiwana jest przez przeglądarkę - nie potrzeba instalować innych aplikacji.

## Szybka instalacja

Aby zainstalować tą aplikację, w folderze Twojego webservera, wykonaj komendę

```
git clone https://github.com/BartoszSiejka/gramy.to
```

lub pobierz plik zip i rozpakuj go.
W katalogu aplikacji wykonaj polecenie

```
composer install
```

Nadaj odpowiednie uprawnienia dla folderów var/cache/, var/log/, public/uploads oraz backup.

Gotowe! 

### Wymagania wstępne

Aby aplikacja działała potrzebujesz LAMP (np. Apache, PHP, MariaDB) z następującymi bibliotekami:

* composer
* curl
* mod_rewrite
* openssl (opcjonalnie)
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
* firewall (opcjonalnie)
* antivir (opcjonalnie)
* dns server (np. dnsmasq, opcjonalnie)

### Konfiguracja

W pliku .env ustaw parametr APP_ENV na prod lub dev i uzupełnij dane dostępowe do bazy:
    
    # w tym wypadku nie używaj spacji przed i po znaku =
    DB_USER=TWOJA_NAZWA_UZYTKOWNIKA
    DB_PASSWORD=TWOJE_HASLO
    DB_NAME=NAZWA_BAZY_DANYCH
    DB_HOST=IP_BAZY_DANYCH
    DB_PORT=PORT_BAZY_DANYCH

Uzupełnij też dane w sekcji parameters w pliku services.yaml

    parameters:
        # ta sekcja zostaje bez zmian
        database:
            user: '%env(DB_USER)%'
            password: '%env(DB_PASSWORD)%'
            name: '%env(DB_NAME)%'
        # w tej sekcji podaj dane do serwera SSH na który będzie wysyłana kopia zapasowa
        ssh:
            host: 
            port:
            user:
            password:
            remoteDir: # pełna ścieżka do pliku kopii zapasowych
            anotherRemoteDir: # pełna ścieżka do pliku kopii zapasowych wykonanych z zewnętrznego źródła
        maxFilesInBackupDirectory: # maksymalna liczba przechowywanych na serwerze i w aplikacji plików kopii zapasowej
        usb:
            mountDirectory:  # punkt montowania pamięci USB 
            maxFilesToSend: # maksymalna liczba plików wysyłanych z kopii zapasowej do pamięci USB
            directoryName: # nazwa folderu z plikami kopii zapasowej

## Autor

* **Bartosz Siejka** - [GitHub](https://github.com/BartoszSiejka) [https://bsiejka.com](https://bsiejka.com)

## Licencja

Projekt jest objęty licencją MIT (nie wliczając użytych wtyczek, które mogą mieć inne warunki licencyjne) - sprawdź szczegóły w pliku [LICENSE.md](LICENSE.md)
