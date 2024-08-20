# CimaFlix API

CimaFlix is a web application that allows users to create accounts, browse movies and TV shows, add them to their favorites, and search for content. This project uses the Laravel framework and is set up to run locally using Docker and Laravel Sail.

## Features

- User authentication (register, login, logout)
- Browse popular and top-rated movies and TV shows
- Add movies and TV shows to your favorites
- Search for movies and TV shows
- View trailers for movies and TV shows

## Requirements

Before setting up the project, make sure you have the following installed:

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Git](https://git-scm.com/downloads)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/HarouneKESSAL/cima-flix.git
cd cima-flix
```

### 2. Set Up the Environment

#### 1. Copy the .env.example file to .env:

```bash
cp .env.example .env
```
#### 2. Update the .env file with your database credentials, API tokens, and other necessary configurations. The following are important configurations:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cima_flix
DB_USERNAME=sail
DB_PASSWORD=password
TMDB_API_KEY=your_tmdb_api_key_here
```
### 3. Build and Start Docker Containers

#### 1. Use Laravel Sail to build and start the Docker containers for the application:

```bash
./vendor/bin/sail up -d
```
This command will start the following containers:

    1- laravel.test: The main application container.
    2- mysql: The MySQL database container.
    3- phpmyadmin: The PHPMyAdmin container for managing your database.

### 4. Run Migrations
   Once the containers are up and running, you need to run the database migrations to set up the database schema:

```bash
./vendor/bin/sail artisan migrate
```
This command will create all the necessary tables in the database as defined in the migrations.

### 5. Access the Application
   You can now access the application in your browser at http://localhost, but you will not need it since this is an API. You can use Postman or any other API client to interact with the API.

To access PHPMyAdmin, go to http://localhost:8080.

### 6. Stopping the Application
To stop the Docker containers when you're done working, use:

```bash
./vendor/bin/sail down
```
This will stop and remove the containers, networks, and volumes associated with your application.

