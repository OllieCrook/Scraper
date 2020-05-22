# Scraper

A simple web scraper that emails me when a new listing is created.

## Problem

Me and my partner are after something. There are many sites we can use to search and they do have the ability to save a search but the email frequency is too low. By the time we get an email a listing can have thousands of views and there is normally only a few units available. We don't have the time to be checking all day and we don't want to.

## Info

- PHP ^7.2.5
- [Laravel-Zero](https://github.com/laravel-zero/laravel-zero)
- [FriendsOfPHP/Goutte](https://github.com/FriendsOfPHP/Goutte) for Symfony HttpBrowser and DOMCrawler
- [Illuminate/Database](https://github.com/illuminate/database)
- [SwiftMailer](https://github.com/swiftmailer/swiftmailer)
- Gmail SMTP
- Heroku with [Heroku Postgres](https://elements.heroku.com/addons/heroku-postgresql) and [Heroku Scheduler](https://elements.heroku.com/addons/scheduler)
