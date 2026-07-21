### MADE WITH SYMFONY

## Run with Docker (Ubuntu base image)

This project now includes a Docker setup using a blank Ubuntu container.

### 1) Build and start

```bash
docker compose up --build
```

### 2) Open the app

http://localhost:8000

### Xdebug

Xdebug is enabled in the container (port `9003`, mode `debug,develop`).
Set your IDE to listen for PHP debug connections on port `9003`.

### 3) Stop

```bash
docker compose down
```


