# Synergestic Effects - WordPress Website

## Table of Contents
---
- [Docker Setup](#docker-setup)
    - [Install Docker on Windows](#install-docker-on-windows)
    - [Up and Running with Docker](#up-and-running-with-docker)
- [WP Installation with Docker](#wp-installation-with-docker)
    - [Database Connection](#database-connection)
- [Docker Tips](#docker-tips)
    - [Shutting Down Docker](#shutting-down-docker)
    - [Container Level Access](#container-level-access)
    - [Updating Docker and Rebuilding Containers](#updating-docker)
    - [SQL GUI Connection](#sql-gui-connection)
- [Theme Development](#theme-development)

## Docker Setup
---

### Install Docker on Windows
https://docs.docker.com/docker-for-windows/install/

### Up and Running with Docker

1. Copy .env.sample to .env
1. Add all empty field information
    1. MYSQL_ROOT_PASSWORD - create your own
    1. MYSQL_DATABASE - create your own
    1. MYSQL_USER - create your own
    1. MYSQL_PASSWORD - create your own
    1. CLIENT_NAME - agency-list
    1. NGROK_AUTH - Auth Token is stored in last pass
    1. NGROK_LOOK_DOMAIN - webserver
    1. NGROK_HEADER - agency-list.local:80
    1. NGROK_PORT - 80
1. Add the following hosts to your hosts file - **you will need to modify the hosts file as an administrator**
    1. `127.0.0.1 agency-list.local`

**Do the following at the root level of project - via terminal in editor or command line**

1. Run `docker volume create agency-list_dev`
    1. This will create a persisted volume
1. Run `docker-compose up -d`
    1. This will build all containers and relevant information. Any file changes will automatically update in the container

You can now visit `http://agency-list.local/` and see the code base - **NOTE: Additional setup may be needed to load website such as WP Installation**

## WP Installation with Docker
---
**If the repository does not have a `wp-config-sample.php` and you do not have an existing `wp-config.php` file the installation screen will not load. Download the sample from https://github.com/WordPress/WordPress/blob/master/wp-config-sample.php and run installer**

1. Run installer
1. DB Connection Info
    1. Use the information you set in you `.env` file for a connection.
    1. Hostname will be the name of the database container - This can be found in the `docker-compose.yml` or use the information below
        1. Database Container Name - `agency-list_db`
    1. Submit your connection data
        1. If there is a connection error run `docker container ls` and ensure your database container is running.

## Docker Tips
---
### Shutting Down Docker
Run `docker-compose down`

### Container Level Access
1. Run `docker ps`
2. Note the name of the web server container - `agency-list_server`
3. Run `docker exec -it agency-list_server /bin/bash`

**You now have container level access. All commands within this container are of bash nature**

### Running Composer in Docker
1. Run `docker ps`
2. Note the name of the composer container - `agency-list_composer`
3. Run `docker exec agency-list_composer composer install`
4. If you need to run composer in a different directory add the `-w` flag to the command `docker exec -w /var/www/html/etc agency-list_composer composer install`

### Updating Docker and Rebuilding Containers

If you should ever need to update the docker file, docker compose file, ini files (anything related to docker) and need to test these changes you can try these 2 options

1. Force Container Recreate - **Works for minor container updates**
    1. With your docker containers down run `docker-compose up -d --force-recreate`
1. Fresh Container Build - **Best Solution but takes a bit more time and will wipe all docker containers and networks system wide. Persisted data is safe**
    1. With all Docker Containers down Run `docker system prune -a`
    1. Once complete rebuild your containers with your changes by running `docker-compose up -d`

### SQL GUI Connection

1. Hostname should be `localhost` and not the database container name
2. Other creds are in your `.env`

### Mailhog
In order to test outgoing mail we have added a local mail container that intercepts all outgoing mail from containers. If you are working with anything mail releated you can check it at
`http://agency-list.local:8025`

### NGROK
Sometimes we need to have other look at the WIP or have api auth callback from public domains. This is where ngrok comes in. This will provide you with a public access url that can hit your local via a tunnel. To access all ngrok information go here `http://agency-list.local:4040`

## Theme Development
Navigate to the `/wp-content/themes/tailpress` directory and run `npm install` and `npm run watch` or `npm run dev`

To build run `npm run production`