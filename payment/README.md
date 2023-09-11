# Payment Laravel



## Getting started

Для работы с проектом необходимо установить PHP и Composer.

В консоли PHP может называться по-разному в зависимости от версии (php, php8.0, php8.1-fpm и тп).

### Установка PHP8.1 (на Debian)
```shell
$ sudo apt install apt-transport-https lsb-release ca-certificates wget -y
$ sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg 
$ sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
$ sudo apt update

# далее установка модулей
$ sudo apt install php8.1 php8.1-common php8.1-cli -y
$ sudo apt install php8.1-{bz2,curl,intl,xml,mysql}
```

### Установка Composer (на Debian)
```Shell
# Установка
$ wget -O composer-setup.php https://getcomposer.org/installer
$ php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Проверка (любая из команд)
$ composer --version
$ composer
```

## Deploy

Установка зависимостей
```Shell
$ composer install
```
Запуск миграции базы данных
```Shell
$ php artisan migrate
```

## Тестовый запуск
```shell
$ php artisan serve
```
