# API de Gerenciamento de E-commerce

Esta é uma API RESTful desenvolvida em PHP 8+ para gerenciar usuários e pedidos em um sistema de e-commerce fictício. A API inclui autenticação por token, integração com serviço de câmbio e operações CRUD completas.

## Funcionalidades

- Gerenciamento de usuários (CRUD)
- Gerenciamento de pedidos (CRUD)
- Cálculo automático de valores totais
- Conversão de moedas (BRL ↔ USD) via API externa
- Autenticação por token JWT
- Paginação em listagens
- Validação de dados

## Pré-requisitos

- PHP 8.4
- Composer
- Postgress 15
- Docker
- Documentação da API com Swagger (OpenAPI)
- Implementando PSR-12 - PHP_CodeSniffer
- Implementação de Testes com PHPUnit

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/ecommerce-api.git
cd ecommerce-api



Instale as dependências: composer install

Configure o ambiente: cp .env.example .env

Edite o arquivo .env com suas configurações de banco de dados e outras variáveis.

php database/migrations.php

Inicie o servidor: php artisan serve

Para testar rode: docker-compose run --rm app_test vendor/bin/phpunit tests

Para validar o PSR-12: vendor/bin/phpcs \
    /var/www/html/app/Http/Controllers \
    /var/www/html/routes \
    /var/www/html/tests \
    --standard=phpcs.xml -s
