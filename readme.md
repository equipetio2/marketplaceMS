# Mercado Livre MicroService

This microservice was made to publish itens on ML

## How to install

- Install Docker
- Clone the project
- Copy .env.example to .env
- Copy docker-compose.yml-example to docker-compose.yml
- Run `docker run --rm -v $(pwd):/app -v ~/.ssh:/root/.ssh composer install`
- Create the container `docker-compose up`
- Acess the localhost or the IP of container
- Use Postam for requests

## How to generate key
- uncomment the lines 17, 18, 19 and acess localhost/key
- copy the code and put in .env