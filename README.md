# 🛒 API de Gerenciamento de E-commerce

&#x20;    &#x20;

API RESTful em **PHP 8+** para gerenciamento de usuários e pedidos, com autenticação JWT, integração de câmbio e suporte completo a CRUD.

---

## ✨ Funcionalidades

* **Usuários:** CRUD completo
* **Pedidos:** CRUD completo com cálculo automático de valores
* **Conversão de moedas:** BRL ↔ USD via API externa
* **Autenticação:** JWT (JSON Web Token)
* **Paginação:** Para todas as listagens
* **Validação de dados:** Regras robustas
* **Documentação:** Swagger/OpenAPI

---

## 🛠️ Tecnologias

| Tecnologia      | Versão / Observações                |
| --------------- | ----------------------------------- |
| PHP             | 8.4                                 |
| PostgreSQL      | 15                                  |
| Docker          | Orquestração de containers          |
| Composer        | Gerenciamento de dependências       |
| Swagger/OpenAPI | Documentação interativa             |
| PSR-12          | Padrão de código (PHP\_CodeSniffer) |
| PHPUnit         | Testes automatizados                |
| PHPStan         | Análise estática de código          |

---

## 🚀 Instalação

```bash
git clone https://github.com/seu-usuario/ecommerce-api.git
cd ecommerce-api

docker-compose down -v
docker-compose build
docker-compose up -d

docker-compose exec app composer install
docker-compose exec app cp .env.example .env

Configura o banco de dados e chave do EXCHANGE_RATE_KEY

# Configurações de Banco de Dados
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=ecommerce
DB_USERNAME=
DB_PASSWORD=

EXCHANGE_RATE_KEY=

docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate

docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000
```

---

## 🧪 Testes e Qualidade de Código

### Testes unitários

```bash
docker-compose run --rm app_test vendor/bin/phpunit tests
```

### PSR-12

```bash
vendor/bin/phpcs /var/www/html/app/Http/Controllers /var/www/html/routes /var/www/html/tests --standard=phpcs.xml -s
```

### PHPStan (Análise Estática)

```bash
vendor/bin/phpstan analyse -c phpstan.neon
```

---

## 📄 Documentação

A documentação interativa está disponível via **Swagger/OpenAPI**:
`http://localhost:8000/api/documentation`

---

## 📂 Estrutura do Projeto

```
app/
├── Http/
│   ├── Controllers/
│   └── Middleware/
config/
database/
tests/
routes/
```