# Personal Project Manager

Personal Project Manager (PPM) is just that, a tool to manage one person's projects. Everything is stored in a single sqlite3 database. It runs as a website, either localhosted or hosted on another machine on the same network.

NOTE: This project is very much still in-progress.

## Installation and Deployment

Note that I'm not a real web developer and likely am not following best practices for real production code. This is a personal project for my own education and use, and at this point is only intended to be run on localhost or at most a private server on your home network. Don't run this in a way that's actually internet-facing.

### PHP development server

PHP has a built-in development server. If you only ever plan on using PPM on a single machine, don't want to deal with Apache, and don't mind manually starting the server, go with this option.

Dependencies: PHP, sqlite3, and the php-sqlite3 extension.

To install: `sudo apt install php sqlite3 php-sqlite3`

1. run `PHP -S localhost:8000 -t path_to_ppm_repo/public`
2. Navigate to localhost:8000 in your browser.
3. When done, simply stop `ctrl-c` the server.

The PHP command starts the development server, setting the public directory of the repo as the document root.

If you want to make this slightly easier, you could set a shell alias to start the development server and send it to the background, sending the logs to a log file in the repo. Add one additional alias to bring the process to the foreground to stop it:

`alias ppmstart="php -S localhost:8000 -t path_to_ppm_repo/public >> path_to_ppm_repo/dev_server.log 2>&1 &"`
`alias ppmfg="fg %php"`

If you use this option, the database file will be `db/ppm.sqlite3` in the repository itself.

### Apache on Ubuntu

This is also set up to be served with Apache. Do this if you don't want to bother starting and stopping the server, or if you want to run this on a server on your home network.

**Note that there's absolutely no authentication, so any device on your network can access the site and the database contents if you use PPM this way.**

On Ubuntu systems, from the top level of the repo, run: `sudo bash ubuntu_apache2_bootstrap.sh`

This script will install/update PHP, apache2, and sqlite3, create the necessary config file and hosting directory for Apache, and then copy the relevant files from the repo to the hosting directory.

If you make (or pull) any changes to the repo, run `sudo bash deploy.sh` to copy the repo to the Apache hosting directory.

If you use this option, the database file will be db/ppm.sqlite3 in the Apache host directory `/var/www/ppm/db/ppm.sqlite3`.
